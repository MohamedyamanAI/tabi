export function activityLevelTextClass(level: number | null | undefined): string {
    if (level === null || level === undefined) {
        return 'text-muted';
    }
    if (level >= 70) {
        return 'text-green-500';
    }
    if (level >= 40) {
        return 'text-yellow-500';
    }
    return 'text-red-500';
}

export function activityLevelBarClass(level: number | null | undefined): string {
    if (level === null || level === undefined) {
        return 'bg-muted';
    }
    if (level >= 70) {
        return 'bg-green-500';
    }
    if (level >= 40) {
        return 'bg-yellow-500';
    }
    return 'bg-red-500';
}
