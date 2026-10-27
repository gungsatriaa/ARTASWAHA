import { Link, usePage } from '@inertiajs/react';
import Dropdown from './Dropdown';
import Icon from './Icon';
import { CategoryIcon } from './ui';
import { Action } from './forms';

const itemClass =
    'flex min-h-12 items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold text-foreground/65 transition hover:bg-primary/15 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent';
export function SubjectLinks({ group, items = [], close }) {
    const { navigation } = usePage().props;
    return (
        <>
            <div className="max-h-80 space-y-1 overflow-y-auto">
                {items.map((item) => (
                    <Link href={item.url} key={item.id} className={itemClass} onClick={close}>
                        <CategoryIcon group={group} title={item.title} className="size-9" />
                        <span className="min-w-0 break-words">{item.title}</span>
                        <Icon name="arrow" className="ml-auto size-4 shrink-0 text-foreground/30" />
                    </Link>
                ))}
                {!items.length && <p className="px-3 py-4 text-sm text-foreground/50">Materi sedang disiapkan.</p>}
            </div>
            <Link
                href={`${navigation.learning}?group=${group}`}
                onClick={close}
                className={`${itemClass} mt-2 !text-accent`}
            >
                Lihat semua materi
                <Icon name="arrow" className="ml-auto size-4" />
            </Link>
        </>
    );
}
export function SubjectDropdown({ group, items, active }) {
    const title = group === 'aksara' ? 'Aksara Bali' : 'Paribasa Bali';
    return (
        <Dropdown
            label={title}
            active={active}
            trigger={
                <>
                    <Icon name={group === 'aksara' ? 'aksara' : 'chat'} />
                    <span>{title}</span>
                </>
            }
        >
            <p className="px-3 pb-2 pt-2 font-display text-lg font-bold">{title}</p>
            <SubjectLinks group={group} items={items} />
        </Dropdown>
    );
}
export function ProfileDropdown() {
    const { viewer, navigation, links } = usePage().props;
    return (
        <Dropdown
            label={`Menu profil ${viewer.name}`}
            align="right"
            trigger={
                <img src={viewer.avatar} alt="" width="40" height="40" className="size-10 rounded-full object-cover" />
            }
        >
            <div className="mb-2 flex items-center gap-3 rounded-2xl bg-background/60 p-3">
                <img
                    src={viewer.avatar}
                    alt=""
                    width="48"
                    height="48"
                    className="size-12 shrink-0 rounded-full object-cover"
                />
                <div className="min-w-0">
                    <p className="break-words font-display text-lg font-bold">{viewer.name}</p>
                    <p className="mt-1 text-xs leading-5 text-accent">
                        {viewer.mode === 'anonymous' ? 'Akun anonim · di perangkat ini' : 'Akun terdaftar'}
                    </p>
                </div>
            </div>
            <nav aria-label="Menu akun">
                <Link href={navigation.profile} className={itemClass}>
                    <Icon name="user" />
                    Profil & progres belajar
                </Link>
                <Link href={links['profile.about']} className={itemClass}>
                    <Icon name="info" />
                    Tentang aplikasi ini
                </Link>
                {viewer.isAdmin && (
                    <Link href={navigation.admin} className={itemClass}>
                        <Icon name="chart" />
                        Dashboard admin
                    </Link>
                )}
            </nav>
            {viewer.mode === 'anonymous' ? (
                <div className="mt-2 grid grid-cols-2 gap-2">
                    <Link
                        href={navigation.login}
                        className={`${itemClass} justify-center whitespace-nowrap !bg-primary/15 !text-accent`}
                    >
                        Masuk
                    </Link>
                    <Link
                        href={navigation.register}
                        className={`${itemClass} justify-center whitespace-nowrap !bg-accent !text-background`}
                    >
                        Daftar akun
                    </Link>
                </div>
            ) : (
                <div className="mt-2">
                    <Action url={navigation.logout} className={`${itemClass} w-full !text-danger`}>
                        <Icon name="login" />
                        Keluar
                    </Action>
                </div>
            )}
        </Dropdown>
    );
}
