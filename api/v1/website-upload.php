<?php
require_once '../../classes/API.php';
require_once '../../classes/SubscriptionManager.php';
 
class WebsiteUploadAPI extends API {
    
    public function handleRequest() {
        // Validate session
        $this->validateSession();
        
        // Check if user has permission
        $this->checkUploadPermission();
        
        switch ($this->requestMethod) {
            case 'POST':
                return $this->uploadWebsite();
            case 'GET':
                return $this->getUploadedFiles();
            case 'DELETE':
                return $this->deleteWebsite();
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Upload website files
     */
    private function uploadWebsite() {
        // Check subscription
        $subManager = new SubscriptionManager($this->school_id);
        
        if (!$subManager->hasFeature('website_hosting')) {
            $this->sendError('Website hosting not available in your plan', 403);
        }
        
        // Check file upload
        if (!isset($_FILES['website']) || $_FILES['website']['error'] !== UPLOAD_ERR_OK) {
            $this->sendError('No file uploaded or upload error', 400);
        }
        
        $file = $_FILES['website'];
        $fileSize = $file['size'] / (1024 * 1024); // Convert to MB
        
        // Check file size
        $maxSize = $this->getSetting('max_upload_size_mb') ?? 100;
        if ($fileSize > $maxSize) {
            $this->sendError("File too large. Max size: {$maxSize}MB", 400);
        }
        
        // Check storage limit
        $storageCheck = $subManager->checkLimit('storage_gb');
        if (!$storageCheck['allowed']) {
            $this->sendError($storageCheck['message'], 403);
        }
        
        // Verify ZIP file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mimeType !== 'application/zip') {
            $this->sendError('Only ZIP files are allowed', 400);
        }
        
        try {
            // Create school directory
            $schoolDir = $this->getSchoolDirectory();
            $uploadDir = $schoolDir . '/website/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Extract ZIP
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) === TRUE) {
                
                // Security: Check for malicious files
                $this->scanZipContents($zip);
                
                // Clear existing files
                $this->clearDirectory($uploadDir);
                
                // Extract
                $zip->extractTo($uploadDir);
                $zip->close();
                
                // Update database
                $this->db->prepare("
                    INSERT INTO file_uploads (school_id, uploaded_by, file_name, file_path, file_size_mb, category)
                    VALUES (?, ?, ?, ?, ?, 'website')
                ")->execute([
                    $this->school_id,
                    $this->user_id,
                    $file['name'],
                    $uploadDir,
                    $fileSize
                ]);
                
                // Update school record
                $this->db->prepare("
                    UPDATE schools 
                    SET website_uploaded = 1, site_path = ?
                    WHERE id = ?
                ")->execute([$uploadDir, $this->school_id]);
                
                // Update storage usage
                $this->updateStorageUsage($fileSize);
                
                // Deploy to subdomain
                $subdomain = $this->getSchoolSubdomain();
                $this->deployWebsite($uploadDir, $subdomain);
                
                $this->logActivity('storage', 'Website uploaded', $fileSize);
                
                $this->sendSuccess([
                    'uploaded' => true,
                    'size_mb' => round($fileSize, 2),
                    'url' => "https://{$subdomain}.eduverse.ng",
                    'files_extracted' => $this->countFiles($uploadDir)
                ], 'Website uploaded successfully');
                
            } else {
                $this->sendError('Failed to extract ZIP file', 500);
            }
            
        } catch (Exception $e) {
            $this->sendError('Upload failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Scan ZIP for malicious files
     */
    private function scanZipContents($zip) {
        $dangerous = ['.exe', '.bat', '.cmd', '.sh', '.app'];
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            foreach ($dangerous as $ext) {
                if (substr($filename, -strlen($ext)) === $ext) {
                    throw new Exception('Dangerous file detected: ' . $filename);
                }
            }
        }
    }
    
    /**
     * Deploy website to subdomain
     */
    private function deployWebsite($sourcePath, $subdomain) {
        $targetPath = "/var/www/school-sites/{$subdomain}";
        
        // Create symlink or copy files
        if (file_exists($targetPath)) {
            // Remove old symlink
            if (is_link($targetPath)) {
                unlink($targetPath);
            }
        }
        
        // Create symlink
        symlink($sourcePath, $targetPath);
        
        // Create/update vhost configuration
        $this->createVirtualHost($subdomain, $targetPath);
        
        // Reload Apache
        exec('sudo service apache2 reload');
    }
    
    /**
     * Create Apache virtual host
     */
    private function createVirtualHost($subdomain, $documentRoot) {
        $vhostConfig = "
<VirtualHost *:80>
    ServerName {$subdomain}.eduverse.ng
    DocumentRoot {$documentRoot}
    
    <Directory {$documentRoot}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/{$subdomain}_error.log
    CustomLog \${APACHE_LOG_DIR}/{$subdomain}_access.log combined
</VirtualHost>
";
        
        $vhostFile = "/etc/apache2/sites-available/{$subdomain}.conf";
        file_put_contents($vhostFile, $vhostConfig);
        
        // Enable site
        exec("sudo a2ensite {$subdomain}.conf");
    }
    
    /**
     * Get school directory path
     */
    private function getSchoolDirectory() {
        $baseDir = __DIR__ . '/../../uploads/schools';
        return $baseDir . '/school-' . $this->school_id;
    }
    
    /**
     * Get school subdomain
     */
    private function getSchoolSubdomain() {
        $stmt = $this->db->prepare("SELECT subdomain FROM schools WHERE id = ?");
        $stmt->execute([$this->school_id]);
        $result = $stmt->fetch();
        return $result['subdomain'];
    }
    
    /**
     * Update storage usage
     */
    private function updateStorageUsage($sizeMB) {
        $sizeGB = $sizeMB / 1024;
        
        $this->db->prepare("
            UPDATE storage_usage 
            SET website_size_gb = ?, 
                total_size_gb = website_size_gb + media_size_gb + backup_size_gb
            WHERE school_id = ?
        ")->execute([$sizeGB, $this->school_id]);
    }
    
    /**
     * Check upload permission
     */
    private function checkUploadPermission() {
        $stmt = $this->db->prepare("
            SELECT can_manage_website 
            FROM school_admins 
            WHERE school_id = ? AND user_id = ?
        ");
        $stmt->execute([$this->school_id, $this->user_id]);
        $admin = $stmt->fetch();
        
        if (!$admin || !$admin['can_manage_website']) {
            $this->sendError('You do not have permission to manage website', 403);
        }
    }
    
    // Helper functions
    private function clearDirectory($dir) {
        if (!is_dir($dir)) return;
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->clearDirectory($file) : unlink($file);
        }
    }
    
    private function countFiles($dir) {
        return count(glob($dir . '/*'));
    }
    
    private function getSetting($key) {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }
}
 
// Initialize and handle request
$api = new WebsiteUploadAPI();
$api->handleRequest();
```
 