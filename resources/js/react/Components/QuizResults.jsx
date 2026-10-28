import { useId, useState } from 'react';
import { AnimatePresence, useReducedMotion } from 'motion/react';
import * as m from 'motion/react-m';
import AnswerFeedback from './AnswerFeedback';
import Icon from './Icon';
import { AksaraText } from './LessonMaterial';

export default function QuizResults({ results }) {
    const headingId = useId();
    const [opened, setOpened] = useState(null);
    const reduced = useReducedMotion();
    const correct = results.filter((result) => result.isCorrect).length;
    const answered = results.filter((result) => result.selected).length;
    const percent = results.length ? Math.round(correct / results.length * 100) : 0;
    return (
        <section aria-label="Hasil jawaban" className="quiz-results space-y-5">
            <div className="flex flex-col items-center gap-5 rounded-card bg-primary/10 p-5 sm:flex-row sm:p-6">
                <div className="relative grid size-24 shrink-0 place-items-center" aria-label={`${percent}% jawaban benar`}>
                    <svg viewBox="0 0 100 100" className="absolute inset-0 size-full -rotate-90" aria-hidden="true">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" strokeWidth="7" className="text-white/5" />
                        <circle
                            cx="50" cy="50" r="42" fill="none" stroke="currentColor" strokeWidth="7"
                            pathLength="100" strokeDasharray="100" strokeDashoffset={100 - percent}
                            strokeLinecap="round" className="quiz-results-ring text-accent"
                            style={{ '--quiz-ring-offset': 100 - percent }}
                        />
                    </svg>
                    <span className="font-sans text-xl font-bold text-accent tabular-nums">{percent}%</span>
                </div>
                <div className="min-w-0 text-center sm:text-left">
                    <h2 id={headingId} className="font-display text-xl font-bold">Hasil jawabanmu</h2>
                    <p className="mt-2 text-sm leading-7 text-foreground/60">Setiap latihan adalah langkah maju. Buka soal untuk melihat pembahasannya.</p>
                    <div className="mt-3 flex flex-wrap justify-center gap-2 text-xs font-semibold sm:justify-start">
                        <span className="rounded-full bg-success/10 px-3 py-2 text-success">{correct} benar</span>
                        <span className="rounded-full bg-danger/10 px-3 py-2 text-danger">{answered - correct} salah</span>
                        {answered < results.length && <span className="rounded-full bg-white/5 px-3 py-2 text-foreground/60">{results.length - answered} tidak dijawab</span>}
                    </div>
                </div>
            </div>
            <h3 className="font-display text-lg font-bold">Pembahasan jawaban</h3>
            <ol className="space-y-3">
                {results.map((result, index) => (
                    <li
                        key={index}
                        className="quiz-result-item overflow-hidden rounded-3xl bg-surface"
                        style={{ '--quiz-result-delay': `${Math.min(index, 6) * 45}ms` }}
                    >
                        <button
                            type="button"
                            aria-expanded={opened === index}
                            aria-controls={`${headingId}-${index}`}
                            onClick={() => setOpened(opened === index ? null : index)}
                            className="flex min-h-20 w-full items-center gap-3 p-4 text-left transition-colors hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent sm:p-5"
                        >
                            <span className={`grid size-10 shrink-0 place-items-center rounded-full ${result.isCorrect ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'}`}>
                                <Icon name={result.isCorrect ? 'check' : 'close'} />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className={`block text-xs font-semibold ${result.isCorrect ? 'text-success' : 'text-danger'}`}>
                                    {index + 1}. {result.isCorrect ? 'Benar' : result.selected ? 'Salah' : 'Tidak dijawab'}
                                </span>{' '}
                                <span className="mt-1 block break-words text-sm font-semibold leading-7">
                                    <AksaraText>{result.prompt}</AksaraText>
                                </span>
                            </span>
                            <Icon name="chevron" className={`size-4 shrink-0 text-foreground/50 transition-transform ${opened === index ? 'rotate-180' : ''}`} />
                        </button>
                        <AnimatePresence initial={false}>
                            {opened === index && (
                                <m.div
                                    id={`${headingId}-${index}`}
                                    initial={{ height: 0, opacity: 0 }}
                                    animate={{ height: 'auto', opacity: 1 }}
                                    exit={{ height: 0, opacity: 0 }}
                                    transition={{ duration: reduced ? 0 : 0.2, ease: 'easeOut' }}
                                    className="overflow-hidden"
                                >
                                    <div className="p-3 pt-0 sm:p-4 sm:pt-0">
                                        <AnswerFeedback
                                            correct={result.isCorrect}
                                            selected={result.selected || 'Tidak dijawab'}
                                            answer={result.correct}
                                            explanation={result.explanation}
                                        />
                                    </div>
                                </m.div>
                            )}
                        </AnimatePresence>
                    </li>
                ))}
            </ol>
        </section>
    );
}
