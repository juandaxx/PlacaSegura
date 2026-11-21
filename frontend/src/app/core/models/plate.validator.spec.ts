import { isValidColombianPlate } from './plate.validator';

describe('isValidColombianPlate', () => {
  it('accepts car and motorcycle plates', () => {
    expect(isValidColombianPlate('ABC123')).toBe(true);
    expect(isValidColombianPlate('abc12d')).toBe(true);
  });

  it('trims spaces and rejects invalid formats', () => {
    expect(isValidColombianPlate(' ABC123 ')).toBe(true);
    expect(isValidColombianPlate('AB123')).toBe(false);
    expect(isValidColombianPlate('ABC12')).toBe(false);
  });
});
