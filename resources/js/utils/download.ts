export function getDesktopDownloadUrl(): string {
    const userAgent = window.navigator.userAgent.toLowerCase();
    
    const isWindows = userAgent.includes('win');
    const isMac = userAgent.includes('mac');
    const isLinux = userAgent.includes('linux');

    // Architecture detection (mostly for Linux AppImage)
    const isArm64 = userAgent.includes('arm64') || userAgent.includes('aarch64');

    if (isWindows) {
        return 'https://pub-e438c5d641984ba181976c5209481fbd.r2.dev/releases/v0.0.1/tabi-Setup-0.0.1.exe';
    }

    if (isLinux) {
        if (isArm64) {
            return 'https://pub-e438c5d641984ba181976c5209481fbd.r2.dev/releases/v0.0.1/tabi-0.0.1-arm64.AppImage';
        }
        // Default to x64 for Linux
        return 'https://pub-e438c5d641984ba181976c5209481fbd.r2.dev/releases/v0.0.1/tabi-0.0.1.AppImage';
    }

    if (isMac) {
        // User mentioned they will add mac later, using GitHub releases as a temporary fallback
        return 'https://github.com/MohamedyamanAI/tabi-desktop/releases';
    }

    // Default to the GitHub releases page for any other OS
    return 'https://github.com/MohamedyamanAI/tabi-desktop/releases';
}

export function handleDesktopDownload(): void {
    const url = getDesktopDownloadUrl();
    window.open(url, '_blank')?.focus();
}
