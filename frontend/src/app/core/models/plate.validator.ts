export function isValidColombianPlate(value: string): boolean {
  return /^[A-Z]{3}(?:\d{3}|\d{2}[A-Z])$/.test(value.trim().toUpperCase());
}
