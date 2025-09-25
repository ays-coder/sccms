// Check if directory exists
if (!is_dir('fpdf')) {
    // Create directory if it doesn't exist
    mkdir('fpdf', 0777, true);
}

// Download FPDF from the official source
$fpdf_url = 'http://www.fpdf.org/en/download/fpdf184.zip';
$zip_file = 'fpdf.zip';

// Download the file
if (file_put_contents($zip_file, file_get_contents($fpdf_url))) {
    echo "Downloaded FPDF successfully\n";
    
    // Create ZIP archive object
    $zip = new ZipArchive;
    
    // Open the zip file
    if ($zip->open($zip_file) === TRUE) {
        // Extract zip file
        $zip->extractTo('fpdf');
        $zip->close();
        echo "Extracted FPDF successfully\n";
        
        // Clean up zip file
        unlink($zip_file);
        echo "Cleaned up temporary files\n";
    } else {
        echo "Failed to extract FPDF\n";
    }
} else {
    echo "Failed to download FPDF\n";
}