$from = "moodle-filter_recitcahiertraces/src/*"
$to = "shared/recitfad3/filter/recitcahiertraces/"

try {
    . ("..\sync\watcher.ps1")
}
catch {
    Write-Host "Error while loading sync.ps1 script." 
}