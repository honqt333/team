export function isLowerOdometer(reading, previous) {
    return (
        reading !== '' &&
        reading != null &&
        Number(previous) > 0 &&
        Number(reading) < Number(previous)
    );
}
