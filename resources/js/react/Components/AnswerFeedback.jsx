import { useEffect, useRef, useState } from 'react';
import Icon from './Icon';
import { Button } from './ui';
import { AksaraText } from './LessonMaterial';

export default function AnswerFeedback({ correct, selected, answer, explanation, onContinue }) {
    const [muted, setMuted] = useState(null);
    useEffect(() => {
        try {
            setMuted(localStorage.getItem('artaswaha-answer-muted') === 'true');
        } catch {
            setMuted(false);
        }
    }, []);
    const played = useRef(false);
    const next = useRef(onContinue);
    next.current = onContinue;
    useEffect(() => {
        if (muted !== false || played.current) return;
        const sound = new Audio(correct ? '/audio/benar.mp3' : '/audio/salah.mp3');
        sound.volume = 0.55;
        const timer = setTimeout(() => {
            played.current = true;
            sound.play()?.catch(() => {});
        }, 0);
        return () => {
            clearTimeout(timer);
            sound.pause();
        };
    }, [correct, muted]);
    useEffect(() => {
        if (!next.current) return;
        const timer = setTimeout(() => next.current?.(), 3000);
        return () => clearTimeout(timer);
    }, []);
    const toggleSound = () => {
        const value = !muted;
        setMuted(value);
        try {
            localStorage.setItem('artaswaha-answer-muted', String(value));
        } catch {
            // The sound control still works when browser storage is unavailable.
        }
    };
    return (
        <div className={`answer-feedback ${correct ? 'answer-feedback-correct bg-success/10' : 'answer-feedback-wrong bg-danger/10'} rounded-card p-5 sm:p-7`}>
            <div className="flex items-start justify-between gap-3">
                <span className={`answer-feedback-icon grid size-14 shrink-0 place-items-center rounded-full ${correct ? 'bg-success text-background' : 'bg-danger text-background'}`}>
                    <Icon name={correct ? 'check' : 'close'} className="size-8" />
                </span>
                <button
                    type="button"
                    onClick={toggleSound}
                    aria-pressed={!muted}
                    className="min-h-11 rounded-full px-3 text-xs font-semibold text-foreground/65 hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent"
                >
                    <span className="flex items-center gap-2">
                        <Icon name="audio" className="size-4" />{muted ? 'Suara mati' : 'Suara aktif'}
                    </span>
                </button>
            </div>
            <h2 role="status" className={`mt-4 font-display text-xl font-bold sm:text-2xl ${correct ? 'text-success' : 'text-danger'}`}>
                {correct ? 'Jawaban benar!' : 'Belum tepat, tetap semangat!'}
            </h2>
            {(selected || answer || explanation) && (
                <div className="mt-4 space-y-2 break-words text-sm leading-7">
                    {selected && <p className="text-foreground/75">Jawabanmu: <AksaraText>{selected}</AksaraText></p>}
                    {!correct && answer && <p className="font-semibold text-success">Jawaban benar: <AksaraText>{answer}</AksaraText></p>}
                    {explanation && <p className="text-foreground/65"><AksaraText>{explanation}</AksaraText></p>}
                </div>
            )}
            {onContinue && (
                <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-xs text-foreground/55">Jawaban tersimpan. Melanjutkan sebentar lagi…</p>
                    <Button type="button" onClick={() => next.current?.()}>Lanjutkan</Button>
                </div>
            )}
        </div>
    );
}
