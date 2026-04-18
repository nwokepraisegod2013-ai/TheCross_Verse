<?php
class WebsiteManager {
    private $db;
    private $uploadDir;
    private $siteDir;
    
    public function __construct($db) {
        $this->db = $db;
        $this->uploadDir = __DIR__ . '/../uploads/schools/';
        $this->siteDir = '/var/www/school-sites/';
        
        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);
        if (!is_dir($this->siteDir)) mkdir($this->siteDir, 0755, true);
    }
    
    public function deploySchoolWebsite($schoolId, $subdomain, $zipPath) {
        $schoolDir = $this->uploadDir . "school-{$schoolId}/website/";
        if (!is_dir($schoolDir)) mkdir($schoolDir, 0755, true);
        
        // Extract ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            throw new Exception('Failed to open ZIP file');
        }
        
        // Validate and extract
        $this->validateZipContents($zip);
        $zip->extractTo($schoolDir);
        $zip->close();
        
        // Detect language
        $language = $this->detectProgrammingLanguage($schoolDir);
        
        // Create symlink
        $symlinkPath = $this->siteDir . $subdomain;
        if (!file_exists($symlinkPath)) {
            symlink($schoolDir, $symlinkPath);
        }
        
        // Generate vhost
        $this->generateVhostConfig($subdomain, $symlinkPath, $language);
        
        // Update database
        $stmt = $this->db->prepare("
            UPDATE schools 
            SET website_deployed = 1, website_language = ?, deployed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$language, $schoolId]);
        
        exec('sudo systemctl reload apache2 2>&1');
        
        return ['success' => true, 'subdomain' => $subdomain, 'language' => $language];
    }
    
    private function validateZipContents($zip) {
        $hasIndex = false;
        $blocked = ['exe', 'bat', 'sh', 'dll', 'com'];
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            
            if (in_array(basename($name), ['index.html', 'index.php'])) $hasIndex = true;
            if (in_array($ext, $blocked)) throw new Exception("Blocked file: {$name}");
        }
        
        if (!$hasIndex) throw new Exception('Must contain index.html or index.php');
        return true;
    }
    
    private function detectProgrammingLanguage($dir) {
        if (file_exists($dir . 'index.php')) return 'php';
        if (file_exists($dir . 'app.py')) return 'python';
        if (file_exists($dir . 'package.json')) return 'nodejs';
        if (file_exists($dir . 'Gemfile')) return 'ruby';
        return 'html';
    }
    
    private function generateVhostConfig($subdomain, $root, $lang) {
        $config = "
<VirtualHost *:80>
    ServerName {$subdomain}.eduverse.ng
    DocumentRoot {$root}
    
    <Directory {$root}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/{$subdomain}-error.log
    CustomLog \${APACHE_LOG_DIR}/{$subdomain}-access.log combined
</VirtualHost>";
        
        @file_put_contents("/etc/apache2/sites-available/{$subdomain}.conf", $config);
        exec("sudo a2ensite {$subdomain}.conf 2>&1");
    }
}