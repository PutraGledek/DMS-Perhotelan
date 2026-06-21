<?php
$files = [
    'penyelesaian.php',
    'penugasan.php',
    'maintenance.php',
    'riwayat.php',
    'surat-tugas.php',
    'detail-track.php',
    'pengaturan.php',
    'inventory.php',
    'register.php',
    'login.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Replace Header
    // Pola: dari ?> \n <!DOCTYPE html> sampai <!-- PAGE CONTENT START -->
    // Ataupun jika tidak ada ?> (misal baris 1 langsung <!DOCTYPE html>)
    // Kita cari <title>(.*?)</title> untuk mengekstrak page title
    preg_match('/<title>(.*?)<\/title>/', $content, $matches);
    $page_title = $matches[1] ?? 'NusaDMS';
    
    // Regex for header replacement
    // Jika diawali dengan ?>, tangkap itu
    $headerPattern = '/(\?>\s*)?<!DOCTYPE html>.*?<!-- PAGE CONTENT START -->/is';
    
    $headerReplacement = "<?php\n\$page_title = \"$page_title\";\nrequire_once 'layout_header.php';\n?>\n            <!-- PAGE CONTENT START -->";
    // Jika file login/register, kita tidak pakai layout admin, jadi skip jika tidak ada PAGE CONTENT START
    if (strpos($content, 'PAGE CONTENT START') !== false) {
        // If the original has ?>, we should keep it or replace it carefully
        // Because $headerReplacement starts with <?php, if there was a ?>, we actually don't need <?php
        // Let's just use:
        $headerReplacement2 = "\$page_title = \"$page_title\";\nrequire_once 'layout_header.php';\n?>\n            <!-- PAGE CONTENT START -->";
        
        $content = preg_replace('/(\?>\s*)<!DOCTYPE html>.*?<!-- PAGE CONTENT START -->/is', $headerReplacement2, $content);
        
        // Handle file yang <!DOCTYPE html> langsung di baris pertama (tidak ada ?>)
        if (strpos($content, '<!DOCTYPE html>') !== false) {
            $headerReplacement3 = "<?php\n\$page_title = \"$page_title\";\nrequire_once 'layout_header.php';\n?>\n            <!-- PAGE CONTENT START -->";
            $content = preg_replace('/<!DOCTYPE html>.*?<!-- PAGE CONTENT START -->/is', $headerReplacement3, $content);
        }
    }
    
    // Replace Footer
    // Untuk file seperti riwayat.php yang punya script, PAGE CONTENT END diikuti text lain
    // Kita harus memotong <!-- PAGE CONTENT END --> sampai </div>
    // layout_footer.php memiliki <!-- PAGE CONTENT END --> \n </main> \n </div> \n <script> \n </body> \n </html>
    
    // Kita ganti dari <!-- PAGE CONTENT END --> sampai </html> dengan memperhatikan script custom
    // Cari apakah ada script custom sesudah PAGE CONTENT END
    
    if (preg_match('/<!-- PAGE CONTENT END -->.*?<\/main>\s*<\/div>\s*(<script>.*?<\/script>)?\s*<\/body>\s*<\/html>/is', $content, $footerMatches)) {
        $customScript = $footerMatches[1] ?? '';
        $footerReplacement = "            <!-- PAGE CONTENT END -->\n";
        if ($customScript) {
            $footerReplacement .= $customScript . "\n";
        }
        $footerReplacement .= "<?php require_once 'layout_footer.php'; ?>\n";
        $content = preg_replace('/<!-- PAGE CONTENT END -->.*?<\/main>\s*<\/div>\s*(<script>.*?<\/script>)?\s*<\/body>\s*<\/html>/is', $footerReplacement, $content);
    }

    file_put_contents($file, $content);
    echo "Refactored: $file\n";
}
echo "Done.";
