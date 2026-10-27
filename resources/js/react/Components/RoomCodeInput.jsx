import { useRef, useState } from 'react';
import { inputClass } from './forms';

export default function RoomCodeInput({ value, onChange, error }) {
    const inputs = useRef([]);
    const [legacy] = useState(() => /^[A-Za-z0-9]{4}(?:[A-Za-z0-9]{6})?$/.test(value));
    const describedBy = error ? 'room-code-error room-code-hint' : 'room-code-hint';
    const fill = (text, index) => {
        if (!/^[0-9]{1,6}$/.test(text)) return;
        const start = text.length === 6 ? 0 : index;
        const digits = value.padEnd(6, ' ').split('');
        [...text].slice(0, 6 - start).forEach((digit, offset) => {
            digits[start + offset] = digit;
        });
        onChange(digits.join('').trimEnd());
        inputs.current[Math.min(start + text.length, 5)]?.focus();
    };
    return (
        <fieldset className="min-w-0">
            <legend className="mb-3 text-sm font-semibold">Kode room</legend>
            {legacy ? (
                <input
                    aria-label="Kode room"
                    className={inputClass}
                    value={value}
                    onChange={(event) => onChange(event.target.value.toUpperCase())}
                    required
                    maxLength={10}
                    pattern="[A-Za-z0-9]{4}([A-Za-z0-9]{6})?"
                    aria-invalid={Boolean(error)}
                    aria-describedby={describedBy}
                />
            ) : (
                <div className="grid grid-cols-6 gap-2 sm:gap-3">
                    {Array.from({ length: 6 }, (_, index) => (
                        <input
                            key={index}
                            ref={(node) => {
                                inputs.current[index] = node;
                            }}
                            aria-label={`Digit ${index + 1} kode room`}
                            aria-invalid={Boolean(error)}
                            aria-describedby={describedBy}
                            className={`aspect-square min-h-11 w-full min-w-0 rounded-2xl bg-background p-0 text-center font-display text-xl font-bold text-foreground outline-none ring-1 transition focus:ring-2 focus:ring-accent sm:text-2xl ${error ? 'ring-danger/60' : 'ring-white/10'}`}
                            inputMode="numeric"
                            autoComplete={index === 0 ? 'one-time-code' : 'off'}
                            required
                            maxLength={1}
                            pattern="[0-9]"
                            value={value[index]?.trim() ?? ''}
                            onFocus={(event) => event.target.select()}
                            onChange={(event) => {
                                if (event.target.value) fill(event.target.value, index);
                                else {
                                    const digits = value.padEnd(6, ' ').split('');
                                    digits[index] = ' ';
                                    onChange(digits.join('').trimEnd());
                                }
                            }}
                            onPaste={(event) => {
                                event.preventDefault();
                                fill(event.clipboardData.getData('text').trim(), index);
                            }}
                            onKeyDown={(event) => {
                                if (event.key === 'Backspace' && !value[index]?.trim() && index > 0) {
                                    event.preventDefault();
                                    const digits = value.padEnd(6, ' ').split('');
                                    digits[index - 1] = ' ';
                                    onChange(digits.join('').trimEnd());
                                    inputs.current[index - 1]?.focus();
                                } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                                    event.preventDefault();
                                    inputs.current[
                                        Math.max(0, Math.min(5, index + (event.key === 'ArrowRight' ? 1 : -1)))
                                    ]?.focus();
                                }
                            }}
                        />
                    ))}
                </div>
            )}
        </fieldset>
    );
}
