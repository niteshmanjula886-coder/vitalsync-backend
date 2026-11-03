$ollamaCmd = Get-Command ollama -ErrorAction SilentlyContinue
if ($ollamaCmd) {
    Write-Host "PATH: $($ollamaCmd.Source)"
} else {
    Write-Host "PATH: Not in PATH"
}

$p1 = "$env:LOCALAPPDATA\Programs\Ollama\ollama.exe"
Write-Host "LocalAppPath: $(Test-Path $p1)"

$p2 = "C:\Program Files\Ollama\ollama.exe"
Write-Host "ProgramFilesPath: $(Test-Path $p2)"

try {
    $res = Invoke-RestMethod -Uri "http://127.0.0.1:11434/" -Method Get -TimeoutSec 2
    Write-Host "HTTP 11434: $res"
} catch {
    Write-Host "HTTP 11434: Offline"
}

