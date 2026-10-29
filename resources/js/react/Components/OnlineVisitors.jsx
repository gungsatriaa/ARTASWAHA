import { Card } from './forms';
import Icon from './Icon';
import usePolling from '../Hooks/usePolling';
import { usePage } from '../routing';

export default function OnlineVisitors() {
    const { links } = usePage().props;
    const { data, error, exhausted } = usePolling(links['admin.analytics.online'], { interval: 15000, limit: 240 });
    return (
        <Card className="!bg-primary/10">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <span className="grid size-11 place-items-center rounded-2xl bg-primary/20 text-accent">
                        <Icon name="user" />
                    </span>
                    <div>
                        <h2 className="font-display text-lg font-bold">Pengunjung aktif</h2>
                        <p className="mt-1 text-xs text-foreground/45">Sinyal aktivitas dalam 2 menit terakhir</p>
                    </div>
                </div>
                <span className="inline-flex items-center gap-2 rounded-full bg-background px-3 py-2 text-xs font-semibold text-foreground/60">
                    <span className={`size-1.5 rounded-full ${error || exhausted ? 'bg-accent' : 'bg-success'}`} />
                    {error || exhausted ? 'Pembaruan dijeda' : 'Diperbarui tiap 15 detik'}
                </span>
            </div>
            <div className="mt-5 flex flex-wrap items-end gap-x-7 gap-y-3">
                <p aria-live="polite" className="admin-number font-sans text-4xl font-bold tabular-nums text-accent">
                    {data?.total ?? '—'}
                </p>
                <p className="pb-1 text-xs text-foreground/55">
                    <strong className="admin-number mr-2 text-foreground">{data?.accounts ?? '—'}</strong>Akun
                </p>
                <p className="pb-1 text-xs text-foreground/55">
                    <strong className="admin-number mr-2 text-foreground">{data?.anonymous ?? '—'}</strong>Tanpa akun
                </p>
            </div>
            {error && (
                <p role="alert" className="mt-3 text-xs text-danger">
                    {error}
                </p>
            )}
            {exhausted && (
                <p className="mt-3 text-xs text-foreground/50">Muat ulang untuk melanjutkan pembaruan langsung.</p>
            )}
        </Card>
    );
}
