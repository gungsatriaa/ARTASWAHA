import { StrictMode, useRef, useState } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, cleanup, fireEvent, render, renderHook, screen, waitFor } from '@testing-library/react';
import Interview from '../../resources/js/react/Pages/Learning/Interview';
import Profile from '../../resources/js/react/Pages/Profile/Show';
import Quizzes from '../../resources/js/react/Pages/Quizzes/Index';
import Editor, { blankQuestion, payloadQuestions, withoutOption } from '../../resources/js/react/Pages/Quizzes/Editor';
import Room, { Countdown } from '../../resources/js/react/Pages/Rooms/Show';
import Lesson from '../../resources/js/react/Pages/Learning/Lesson';
import Auth from '../../resources/js/react/Pages/Auth/Form';
import Privacy from '../../resources/js/react/Pages/Privacy';
import LearningPicker from '../../resources/js/react/Components/LearningPicker';
import Home from '../../resources/js/react/Pages/Home';
import Analysis from '../../resources/js/react/Components/Analysis';
import Keyboard from '../../resources/js/react/Components/Keyboard';
import AvatarCrop from '../../resources/js/react/Components/AvatarCrop';
import AudioPlayer from '../../resources/js/react/Components/AudioPlayer';
import usePolling from '../../resources/js/react/Hooks/usePolling';
import useRoom from '../../resources/js/react/Hooks/useRoom';

const { page, send, reload, visit } = vi.hoisted(() => ({
    page: { props: {} },
    send: vi.fn(),
    reload: vi.fn(),
    visit: vi.fn(),
}));
vi.mock('@inertiajs/react', async () => {
    const { useState, useRef } = await import('react');
    return {
        usePage: () => page,
        Head: () => null,
        Link: ({ children, replace, preserveScroll, ...props }) => <a {...props}>{children}</a>,
        router: { on: () => () => {}, reload, get: visit, visit },
        useForm: (initial) => {
            const [data, setData] = useState(initial);
            const transform = useRef((value) => value);
            const form = {
                data,
                errors: {},
                processing: false,
                isDirty: false,
                setData: (key, value) =>
                    setData((current) => (typeof key === 'string' ? { ...current, [key]: value } : key)),
                transform: (callback) => {
                    transform.current = callback;
                    return form;
                },
                reset: () => setData(initial),
            };
            for (const method of ['post', 'put', 'delete'])
                form[method] = (url, options) => send(method, url, transform.current(data), options);
            return form;
        },
    };
});
vi.mock('motion/react-m', () => ({
    button: ({ children, whileTap, ...props }) => <button {...props}>{children}</button>,
}));
const meta = { title: 'ARTASWAHA' };
const path = {
    id: 1,
    title: 'Cecimpedan',
    description: 'Teka-teki Bali.',
    group: 'paribasa',
    lessonsCount: 2,
    completed: 1,
    url: '/belajar/cecimpedan',
};
const question = { id: 1, prompt: 'Pilih jawaban', options: ['Salah', 'Benar'], keyboard_auto_open: false };
const room = {
    code: 'ABCD',
    title: 'Kuis',
    status: 'active',
    participants: [],
    count: 2,
    position: 1,
    question,
    feedback: false,
    correctOption: null,
    serverNow: '2026-10-27T00:00:00Z',
    urls: { show: 'http://localhost/room/ABCD', status: '/room/status', answer: '/room/answer', leave: '/room/leave' },
    results: [],
};
beforeEach(() => {
    page.props = {
        viewer: { name: 'Made', avatar: '/avatar/penyu.webp', mode: 'anonymous' },
        navigation: {
            home: '/',
            learning: '/belajar',
            profile: '/profil',
            quizzes: '/kuis',
            privacy: '/kebijakan-privasi',
            login: '/login',
            register: '/register',
        },
        links: {
            'learning.interview.store': '/interview',
            'learning.interview.show': '/mulai-belajar',
            'rooms.join': '/room/join',
            'profile.analysis.show': '/analysis',
            'quizzes.store': '/kuis',
            'profile.about': '/profil/tentang',
        },
        brand: { mascot: '/image/asset1.webp', avatarBase: '/avatar' },
        flash: {},
    };
    send.mockReset();
    reload.mockReset();
    visit.mockReset();
    Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'visible' });
    Object.defineProperty(HTMLDialogElement.prototype, 'showModal', {
        configurable: true,
        value: function () {
            this.setAttribute('open', '');
        },
    });
    Object.defineProperty(HTMLDialogElement.prototype, 'close', {
        configurable: true,
        value: function () {
            this.removeAttribute('open');
        },
    });
    vi.spyOn(HTMLMediaElement.prototype, 'pause').mockImplementation(() => {});
});
afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

it('retains several interview selections and submits their IDs, not a single radio value', () => {
    render(
        <Interview
            paths={[path, { ...path, id: 2, title: 'Peparikan' }]}
            answers={{}}
            step={1}
            roles={{}}
            meta={meta}
        />,
    );
    fireEvent.click(screen.getByRole('checkbox', { name: /Cecimpedan/ }));
    fireEvent.click(screen.getByRole('checkbox', { name: /Peparikan/ }));
    fireEvent.submit(screen.getByRole('button', { name: /Lanjutkan/ }).closest('form'));
    expect(send.mock.calls[0][2].learning_path_ids).toEqual([1, 2]);
    fireEvent.click(screen.getByRole('checkbox', { name: /Cecimpedan/ }));
    expect(screen.getByRole('checkbox', { name: /Peparikan/ }).checked).toBe(true);
});

it('requires login only when an anonymous learner tries to create a quiz', () => {
    render(<Quizzes quizzes={null} filters={{}} meta={meta} />);
    fireEvent.click(screen.getByRole('button', { name: /Buat kuis/ }));
    expect(screen.getByRole('dialog')).toBeTruthy();
    expect(send).not.toHaveBeenCalled();
    fireEvent.click(screen.getByRole('button', { name: 'Tutup' }));
    fireEvent.change(screen.getByRole('textbox', { name: 'Kode room' }), { target: { value: 'abcd' } });
    fireEvent.submit(screen.getByRole('button', { name: /Gabung room/ }).closest('form'));
    expect(send.mock.calls[0].slice(0, 3)).toEqual(['post', '/room/join', { code: 'ABCD' }]);
});

it('preserves saved question IDs while removing UI-only keys and correcting a deleted choice', () => {
    const original = {
        ...blankQuestion(1),
        id: 9,
        options: ['A', 'B', 'C'],
        correct_option: 2,
        audio: { url: 'private' },
    };
    const changed = withoutOption(original, 0);
    expect(changed.correct_option).toBe(1);
    expect(withoutOption(original, 2).correct_option).toBe('');
    expect(payloadQuestions([changed])[0]).toEqual(
        expect.objectContaining({ id: 9, options: ['B', 'C'], correct_option: 1 }),
    );
    expect(payloadQuestions([changed])[0]).not.toHaveProperty('_key');
    expect(payloadQuestions([changed])[0]).not.toHaveProperty('audio');
});

it('submits quiz settings through Inertia and keeps preview free of write requests', () => {
    render(<Editor quiz={null} meta={meta} />);
    fireEvent.change(screen.getByRole('textbox', { name: 'Judul kuis' }), { target: { value: 'Belajar Bali' } });
    fireEvent.click(screen.getByRole('button', { name: /Preview & simpan/ }));
    expect(send).not.toHaveBeenCalled();
    fireEvent.submit(screen.getByRole('button', { name: 'Simpan kuis' }).closest('form'));
    expect(send.mock.calls[0][2].title).toBe('Belajar Bali');
    expect(send.mock.calls[0][2].questions[0]).not.toHaveProperty('_key');
});

it('shows only studied paths in the profile and keeps the quiz join action out of its menu', () => {
    render(<Profile paths={[path]} interests={[]} completedLessons={1} totalLessons={2} avatars={[]} meta={meta} />);
    expect(screen.getByText('50%')).toBeTruthy();
    expect(screen.queryByText('Gabung kuis')).toBeNull();
    const header = screen.getByRole('heading', { name: 'Made', level: 1 }).closest('header');
    expect(header.contains(screen.getByText('Akun anonim · hanya di perangkat ini'))).toBe(true);
    expect(header.contains(screen.getByRole('link', { name: 'Daftar akun', exact: true }))).toBe(true);
    expect(screen.getAllByRole('link', { name: 'Daftar akun', exact: true })).toHaveLength(1);
    expect(screen.queryByText('Belajar sebagai Made')).toBeNull();
});

it('renders public content, privacy disclosures and testimonials as escaped React text', () => {
    const { unmount } = render(<Home paths={[path]} selectedIds={[]} hasInterview={true} meta={meta} />);
    expect(document.querySelectorAll('blockquote')).toHaveLength(3);
    expect(screen.getByText(/bukan testimoni pengguna nyata/)).toBeTruthy();
    unmount();
    render(<Privacy meta={meta} />);
    for (const id of ['data', 'tujuan', 'cookie', 'layanan', 'penyimpanan', 'kendali', 'kontak'])
        expect(document.getElementById(id)).toBeTruthy();
    expect(screen.getByRole('link', { name: 'kerthajayasoftware@gmail.com' }).href).toBe(
        'mailto:kerthajayasoftware@gmail.com',
    );
});

it.each(['login', 'register', 'forgot-password', 'reset-password'])(
    'renders the %s form without exposing a password',
    (mode) => {
        render(<Auth mode={mode} email="made@example.test" token="test" meta={meta} />);
        expect(document.querySelector('form')).toBeTruthy();
        expect(document.querySelector('input[type=password]')?.value ?? '').toBe('');
        if (['login', 'register'].includes(mode)) expect(screen.queryByText(/Lihat cara kami menggunakan/)).toBeNull();
    },
);

it('escapes lesson and AI-provider output instead of executing HTML', () => {
    const attack = '<img src=x onerror=alert(1)>';
    render(<Lesson lesson={{ title: 'Uji', content: attack, path }} meta={meta} />);
    render(<Analysis analysis={{ status: 'completed', summary: attack, recommendations: [] }} />);
    expect(screen.getAllByText(attack)).toHaveLength(2);
    expect(document.querySelector('[onerror]')).toBeNull();
});

it('edits Balinese scratch text without losing it when keyboard modes change', () => {
    render(<Keyboard autoOpen />);
    fireEvent.click(screen.getByRole('button', { name: 'Ka', exact: true }));
    expect(screen.getByRole('textbox').value).toBe('ᬓ');
    fireEvent.click(screen.getByRole('button', { name: 'pangangge', exact: true }));
    fireEvent.click(screen.getByRole('button', { name: 'Ulu', exact: true }));
    expect(screen.getByRole('textbox').value).toBe('ᬓᬶ');
});

it('rejects oversized avatars before processing and offers a way back', () => {
    const cancel = vi.fn();
    render(
        <AvatarCrop
            file={new File([new Uint8Array(2 * 1024 * 1024 + 1)], 'large.png', { type: 'image/png' })}
            onApply={vi.fn()}
            onCancel={cancel}
        />,
    );
    expect(screen.getByRole('alert').textContent).toContain('maksimal 2 MB');
    fireEvent.click(screen.getByRole('button', { name: 'Batalkan unggahan' }));
    expect(cancel).toHaveBeenCalledOnce();
});

it('uses server-relative time, expires once, and clears countdown timers when unmounted', () => {
    vi.useFakeTimers();
    const expire = vi.fn();
    const { unmount } = render(
        <Countdown
            deadline="2026-10-27T00:00:02Z"
            serverNow="2026-10-27T00:00:00Z"
            seconds={2}
            label="Sisa waktu"
            onExpire={expire}
        />,
    );
    expect(screen.getByRole('timer').textContent).toBe('0:02');
    act(() => vi.advanceTimersByTime(2000));
    expect(expire).toHaveBeenCalledOnce();
    unmount();
    expect(vi.getTimerCount()).toBe(0);
});

it('renders server feedback and cancels the delayed next-question visit on departure', () => {
    vi.useFakeTimers();
    const { unmount } = render(
        <Room
            room={{ ...room, feedback: true, correctOption: 1, answer: { answer_index: 0, is_correct: false } }}
            meta={meta}
        />,
    );
    expect(screen.getByText('Jawaban benar')).toBeTruthy();
    expect(screen.getByText('Jawabanmu')).toBeTruthy();
    expect(screen.queryByText('Salin tautan undangan')).toBeNull();
    unmount();
    act(() => vi.advanceTimersByTime(4000));
    expect(visit).not.toHaveBeenCalled();
});

it('never marks a correct choice before the server permits feedback', () => {
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({
            ok: true,
            status: 204,
            headers: new Headers({ 'X-Room-Status': 'active', 'X-Room-Participants': '0' }),
        }),
    );
    render(<Room room={room} meta={meta} />);
    expect(screen.queryByText('Jawaban benar')).toBeNull();
    fireEvent.click(screen.getByRole('radio', { name: /Benar/ }));
    fireEvent.submit(screen.getByRole('button', { name: 'Kirim jawaban' }).closest('form'));
    expect(send.mock.calls[0][2]).toEqual({ question_id: 1, answer_index: 1 });
});

it('retains terminal polling data and aborts in-flight requests during Strict Mode cleanup', async () => {
    const signals = [];
    vi.stubGlobal(
        'fetch',
        vi.fn().mockImplementation((url, options) => {
            signals.push(options.signal);
            return Promise.resolve({ ok: true, json: async () => ({ status: 'completed' }) });
        }),
    );
    const { result, rerender, unmount } = renderHook(({ active }) => usePolling('/status', { active }), {
        initialProps: { active: true },
        wrapper: StrictMode,
    });
    await waitFor(() => expect(result.current.data?.status).toBe('completed'));
    expect(signals[0].aborted).toBe(true);
    rerender({ active: false });
    expect(result.current.data.status).toBe('completed');
    unmount();
});

it('stops requests after a terminal room response', async () => {
    vi.useFakeTimers();
    const fetch = vi.fn().mockResolvedValue({ status: 410 });
    vi.stubGlobal('fetch', fetch);
    const { result, unmount } = renderHook(() => useRoom(room, page.props.viewer, 'test'));
    await act(async () => {});
    expect(result.current.unavailable).toBe('inactive');
    await act(async () => vi.advanceTimersByTimeAsync(10000));
    expect(fetch).toHaveBeenCalledOnce();
    unmount();
});

it('stops audio at the server deadline and removes playback listeners on unmount', () => {
    vi.useFakeTimers();
    const { unmount } = render(
        <AudioPlayer
            audio={{ url: '/private-audio' }}
            deadline="2026-10-27T00:00:02Z"
            serverNow="2026-10-27T00:00:00Z"
        />,
    );
    act(() => vi.advanceTimersByTime(2000));
    expect(HTMLMediaElement.prototype.pause).toHaveBeenCalledOnce();
    unmount();
    expect(vi.getTimerCount()).toBe(0);
});

it('keeps Balinese characters in a controlled editor field after another render', () => {
    function EditorField() {
        const target = useRef(null);
        const [value, setValue] = useState('');
        const [count, setCount] = useState(0);
        return (
            <>
                <input
                    aria-label="Teks editor"
                    ref={target}
                    value={value}
                    onChange={(event) => setValue(event.target.value)}
                />
                <button onClick={() => setCount(count + 1)}>Render ulang {count}</button>
                <Keyboard targetRef={target} autoOpen />
            </>
        );
    }
    render(<EditorField />);
    fireEvent.click(screen.getByRole('button', { name: 'Ka', exact: true }));
    fireEvent.click(screen.getByRole('button', { name: /Render ulang/ }));
    expect(screen.getByRole('textbox', { name: 'Teks editor' }).value).toBe('ᬓ');
});

it('does not carry the anonymous-mode transform into the name submission', () => {
    page.props.viewer = null;
    const { rerender } = render(
        <Interview
            paths={[path]}
            answers={{ learning_path_ids: [1], role: 'murid' }}
            step={3}
            roles={{}}
            meta={meta}
        />,
    );
    fireEvent.click(screen.getByRole('button', { name: 'Pilih anonim' }));
    rerender(
        <Interview
            paths={[path]}
            answers={{ learning_path_ids: [1], role: 'murid', mode: 'anonymous' }}
            step={4}
            roles={{}}
            avatar="/avatar/penyu.webp"
            meta={meta}
        />,
    );
    fireEvent.change(screen.getByRole('textbox', { name: 'Nama panggilan' }), { target: { value: 'Made' } });
    fireEvent.submit(screen.getByRole('button', { name: /Mulai belajar/ }).closest('form'));
    expect(send.mock.calls.at(-1)[2]).toEqual(expect.objectContaining({ step: 4, name: 'Made' }));
});

const adminPages = import.meta.glob('../../resources/js/react/Pages/Admin/*.jsx');
const collection = { data: [], total: 0, current_page: 1, last_page: 1 };
const adminFixtures = {
    Dashboard: { counts: {}, tasks: {}, paths: [path], lessons: [] },
    Analytics: { chart: { labels: [], users: [], completions: [], answers: [] }, metrics: {}, topPaths: [] },
    Paths: { paths: collection },
    PathForm: { path: {} },
    Lessons: { lessons: collection, paths: [path], filters: {} },
    LessonForm: { lesson: {}, paths: [path] },
    Preview: { lesson: { ...question, title: 'Modul uji', content: 'Isi materi', exercise_prompt: null } },
    AudioIndex: { items: collection, type: 'records', filters: {}, configured: false },
    AudioEdit: { title: 'Narasi', text: 'Materi uji', action: '/audio', configured: false, audio: null },
    Ai: {
        tab: 'overview',
        start: '2026-10-01',
        end: '2026-10-27',
        snapshot: {},
        statuses: [],
        profiles: collection,
        drafts: collection,
        reports: collection,
        paths: [path],
        filters: {},
        audioCounts: {},
    },
    AiDetail: {
        kind: 'report',
        record: {
            id: 1,
            status: 'completed',
            snapshot: {},
            result: { summary: '<script>bad()</script>', observations: [], suggestions: [] },
        },
    },
};
it.each(Object.entries(adminFixtures))('renders the admin %s page with sparse real-world data', async (name, props) => {
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({ ok: true, json: async () => ({ total: 0, accounts: 0, anonymous: 0 }) }),
    );
    page.props.viewer = { ...page.props.viewer, mode: 'account', isAdmin: true };
    const { default: Page } = await adminPages[`../../resources/js/react/Pages/Admin/${name}.jsx`]();
    await act(async () => {
        render(<Page {...props} meta={meta} />);
    });
    expect(document.querySelector('h1')).toBeTruthy();
    expect(document.querySelector('script')).toBeNull();
});

it('does not deactivate the host room on a document reload or a Strict Mode effect remount', async () => {
    const beacon = vi.fn();
    Object.defineProperty(navigator, 'sendBeacon', { configurable: true, value: beacon });
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({
            ok: true,
            status: 204,
            headers: new Headers({ 'X-Room-Status': 'active', 'X-Room-Participants': '0' }),
        }),
    );
    const { unmount } = renderHook(
        () =>
            useRoom(
                { ...room, isHost: true, urls: { ...room.urls, heartbeat: '/heartbeat' } },
                { mode: 'account' },
                'test',
            ),
        { wrapper: StrictMode },
    );
    await act(async () => {});
    act(() => window.dispatchEvent(new Event('pagehide')));
    expect(beacon).not.toHaveBeenCalled();
    unmount();
});

it('offers a document reload when a page chunk cannot be resolved', async () => {
    const { resolve } = await import('../../resources/js/react/resolve');
    const { default: FailedPage } = await resolve('Missing/Chunk');
    page.url = '/tujuan';
    render(<FailedPage />);
    expect(screen.getByRole('link', { name: 'Muat ulang halaman' }).getAttribute('href')).toBe('/tujuan');
});

it('keeps account profiles free of anonymous registration prompts', () => {
    page.props.viewer = { ...page.props.viewer, mode: 'account' };
    render(
        <Profile
            paths={[]}
            interests={[]}
            completedLessons={0}
            totalLessons={0}
            avatars={[]}
            email="made@example.test"
            meta={meta}
        />,
    );
    expect(screen.getByText('Akun terdaftar')).toBeTruthy();
    expect(screen.getByText('made@example.test')).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Daftar akun', exact: true })).toBeNull();
    expect(screen.queryByText('Akun anonim · hanya di perangkat ini')).toBeNull();
});

it('walks through category and material selection before opening a lesson path', () => {
    const close = vi.fn();
    render(<LearningPicker paths={[path]} onClose={close} />);
    expect(screen.getByRole('button', { name: 'Lanjutkan →' }).disabled).toBe(true);
    fireEvent.click(screen.getByRole('radio', { name: /Paribasa Bali/ }));
    fireEvent.click(screen.getByRole('button', { name: 'Lanjutkan →' }));
    expect(screen.getByText('Pilih materi Paribasa Bali')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Mulai belajar →' }).disabled).toBe(true);
    fireEvent.click(screen.getByRole('radio', { name: /Cecimpedan/ }));
    expect(screen.getByRole('link', { name: 'Mulai belajar →' }).getAttribute('href')).toBe(path.url);
    fireEvent.click(screen.getByRole('button', { name: '← Kembali' }));
    expect(screen.getByRole('radio', { name: /Paribasa Bali/ }).checked).toBe(true);
});

it('opens the route-backed interview as a modal with four named steps', () => {
    render(<Interview paths={[path]} answers={{}} step={1} roles={{}} meta={meta} />);
    expect(screen.getByRole('dialog', { name: 'Mulai perjalanan belajarmu' })).toBeTruthy();
    expect(screen.getByRole('list', { name: 'Langkah 1 dari 4' }).children).toHaveLength(4);
    fireEvent.click(screen.getByRole('button', { name: 'Tutup' }));
    expect(visit).toHaveBeenCalledWith('/');
});
