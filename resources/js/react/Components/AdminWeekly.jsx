import { Card } from './forms';
import Icon from './Icon';

export default function AdminWeekly({ weekly = {} }) {
    return (
        <section aria-label="Perbandingan aktivitas mingguan" className="grid gap-3 sm:grid-cols-3">
            {[
                ['completions', 'Modul selesai', 'book'],
                ['answers', 'Jawaban kuis', 'quiz'],
                ['users', 'Akun baru', 'user'],
            ].map(([key, label, icon]) => {
                const current = weekly[key]?.current ?? 0;
                const previous = weekly[key]?.previous ?? 0;
                const change = current - previous;
                return (
                    <Card key={key} className="!p-5">
                        <div className="flex items-center justify-between gap-2 text-xs text-foreground/55">
                            <h3>{label}</h3>
                            <Icon name={icon} className="size-4 text-accent" />
                        </div>
                        <p className="mt-4 font-sans text-3xl font-bold tabular-nums">{current}</p>
                        <p className="mt-3 text-xs leading-6 text-foreground/50">
                            <span
                                className={
                                    change > 0
                                        ? 'font-semibold text-success'
                                        : change < 0
                                          ? 'font-semibold text-accent'
                                          : ''
                                }
                            >
                                {change > 0 ? `+${change}` : change}
                            </span>{' '}
                            dibanding 7 hari sebelumnya ({previous})
                        </p>
                    </Card>
                );
            })}
        </section>
    );
}
