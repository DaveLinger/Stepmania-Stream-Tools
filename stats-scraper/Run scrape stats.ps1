$FileName = "C:\Users\Admin\AppData\Roaming\StepMania 5.1\Save\LocalProfiles\00000000\Stats.xml"
$FileTime = Get-Date
$Frequency = 5
$phpExe = "D:\php\php.exe"
$phpFile = "D:\Song Scraper\scrape_stats.php"

# Set-PSDebug -Trace 1

# endless loop
for () {
    $file = Get-Item $FileName
    if ($FileTime -ne $file.LastWriteTime) {
        $phpOutput = & $phpExe $phpFile
		echo $phpOutput
    }
    $FileTime = $file.LastWriteTime
    Start-Sleep $Frequency
}