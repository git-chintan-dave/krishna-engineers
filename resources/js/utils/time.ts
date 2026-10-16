export const secondsToTime = (totalSeconds: number): string => {
    const hours = Math.floor(totalSeconds / 3600);

    const minutes = Math.floor((totalSeconds % 3600) / 60);

    const seconds = totalSeconds % 60;

    return [hours, minutes, seconds]
        .map((value) => String(value).padStart(2, '0'))
        .join(':');
};

export const timeToSeconds = (value: string): number => {
    const parts = value.split(':').map(Number);

    if (parts.length !== 3 || parts.some((part) => !Number.isFinite(part))) {
        return 0;
    }

    const [hours, minutes, seconds] = parts;

    return hours * 3600 + minutes * 60 + seconds;
};
