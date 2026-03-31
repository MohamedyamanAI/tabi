const R2_BASE = 'https://pub-e438c5d641984ba181976c5209481fbd.r2.dev/releases/latest'

export function isMacClient(): boolean {
    return window.navigator.userAgent.toLowerCase().includes('mac')
}

export function getMacDesktopDownloadUrl(arch: 'arm64' | 'x64'): string {
    return arch === 'arm64' ? `${R2_BASE}/tabi-arm64.dmg` : `${R2_BASE}/tabi-x64.dmg`
}

export function getDesktopDownloadUrl(): string {
    const userAgent = window.navigator.userAgent.toLowerCase()
    const platform = (window.navigator.platform || '').toLowerCase()

    const isWindows = userAgent.includes('win')
    const isMac = userAgent.includes('mac')
    const isLinux = userAgent.includes('linux')
    const isArm64 = userAgent.includes('arm64') || userAgent.includes('aarch64')
    const isMacArm64 =
        isMac && (isArm64 || userAgent.includes('apple silicon') || platform.includes('arm'))

    if (isWindows) {
        return isArm64
            ? `${R2_BASE}/tabi-Setup-arm64.exe`
            : `${R2_BASE}/tabi-Setup.exe`
    }

    if (isMac) {
        return isMacArm64 ? `${R2_BASE}/tabi-arm64.dmg` : `${R2_BASE}/tabi-x64.dmg`
    }

    if (isLinux) {
        return isArm64
            ? `${R2_BASE}/tabi-arm64.AppImage`
            : `${R2_BASE}/tabi.AppImage`
    }

    // Fallback — default to Windows x64
    return `${R2_BASE}/tabi-Setup.exe`
}

export function openDesktopDownloadUrl(url: string): void {
    window.open(url, '_blank')?.focus()
}

export function handleDesktopDownload(): void {
    const url = getDesktopDownloadUrl()
    openDesktopDownloadUrl(url)
}