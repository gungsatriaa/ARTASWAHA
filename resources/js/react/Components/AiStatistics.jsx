import { Stat } from './forms';
export default function AiStatistics({ snapshot }) {
    return (
        <>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    ['new_accounts', 'Akun baru'],
                    ['new_interviews', 'Interview tersimpan'],
                    ['completed_lessons', 'Penyelesaian modul'],
                    ['quiz_answers', 'Jawaban kuis'],
                ].map(([key, label]) => (
                    <Stat key={key} label={label} value={snapshot?.[key] ?? 0} />
                ))}
            </div>
            <p className="mt-4 text-xs leading-6 text-foreground/45">
                Angka dihitung dari database untuk periode ini. Jawaban benar: {snapshot?.correct_answers ?? 0} dari{' '}
                {snapshot?.quiz_answers ?? 0}. Penyelesaian modul adalah jumlah aktivitas, bukan jumlah pelajar unik.
            </p>
        </>
    );
}
