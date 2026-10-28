import { StrictMode, useRef, useState } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, cleanup, fireEvent, render, renderHook, screen, waitFor, within } from '@testing-library/react';
import Interview from '../../resources/js/react/Pages/Learning/Interview';
import Profile from '../../resources/js/react/Pages/Profile/Show';
import AdminLayout from '../../resources/js/react/Layouts/AdminLayout';
import AdminWeekly from '../../resources/js/react/Components/AdminWeekly';
import OnlineVisitors from '../../resources/js/react/Components/OnlineVisitors';
import Quizzes from '../../resources/js/react/Pages/Quizzes/Index';
import Editor, { blankQuestion, payloadQuestions, withoutOption } from '../../resources/js/react/Pages/Quizzes/Editor';
import Room, { Countdown } from '../../resources/js/react/Pages/Rooms/Show';
import Lesson from '../../resources/js/react/Pages/Learning/Lesson';
import LearningPath from '../../resources/js/react/Pages/Learning/Path';
import Auth from '../../resources/js/react/Pages/Auth/Form';
import AudioEdit from '../../resources/js/react/Pages/Admin/AudioEdit';
import Privacy from '../../resources/js/react/Pages/Privacy';
import Faq from '../../resources/js/react/Components/Faq';
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
                errors: page.props.errors ?? {},
                processing: false,
                isDirty: false,
                setData: (key, value) =>
                    setData((current) => (typeof key === 'string' ? { ...current, [key]: value } : key)),
                transform: (callback) => {
                    transform.current = callback;
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
            'profile.destroy': '/profil',
            'auth.google.redirect': '/auth/google',
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
            paths={[path, { ...path, id: 2, title: 'Aksara Wreastra', group: 'aksara' }]}
            answers={{}}
            step={2}
            roles={{}}
            meta={meta}
        />,
    );
    fireEvent.click(screen.getByRole('checkbox', { name: /Cecimpedan/ }));
    fireEvent.click(screen.getByRole('checkbox', { name: /Aksara Wreastra/ }));
    fireEvent.submit(screen.getByRole('button', { name: /Lanjutkan/ }).closest('form'));
    expect(send.mock.calls[0][2].learning_path_ids).toEqual([1, 2]);
    fireEvent.click(screen.getByRole('checkbox', { name: /Cecimpedan/ }));
    expect(screen.getByRole('checkbox', { name: /Aksara Wreastra/ }).checked).toBe(true);
});

it.each([
    ['Simpan narasi', 'save'],
    ['Buat audio offline', 'generate'],
    ['Gunakan rekaman', 'upload'],
])('submits the %s audio action when transform returns void', (label, action) => {
    render(<AudioEdit title="Narasi" text="Materi Bali" action="/admin/audio/modul/1" configured meta={meta} />);
    const button = screen.getByRole('button', { name: label });
    fireEvent(
        button.closest('form'),
        new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: button }),
    );
    expect(send.mock.calls[0].slice(0, 3)).toEqual([
        'post',
        '/admin/audio/modul/1',
        expect.objectContaining({ action, spoken_text: 'Materi Bali' }),
    ]);
});

it('requires login only when an anonymous learner tries to create a quiz', () => {
    render(<Quizzes quizzes={null} filters={{}} meta={meta} />);
    fireEvent.click(screen.getByRole('button', { name: /Buat kuis/ }));
    expect(screen.getByRole('dialog')).toBeTruthy();
    expect(send).not.toHaveBeenCalled();
    fireEvent.click(screen.getByRole('button', { name: 'Tutup' }));
    fireEvent.paste(screen.getByRole('textbox', { name: 'Digit 1 kode room' }), {
        clipboardData: { getData: () => '012345' },
    });
    fireEvent.submit(screen.getByRole('button', { name: /Gabung room/ }).closest('form'));
    expect(send.mock.calls[0].slice(0, 3)).toEqual(['post', '/room/join', { code: '012345' }]);
});

it('supports six code boxes, automatic focus, replacement, backspace, arrows and paste', () => {
    render(<Quizzes quizzes={null} filters={{}} meta={meta} />);
    const digits = screen.getAllByRole('textbox');
    expect(digits).toHaveLength(6);
    fireEvent.change(digits[0], { target: { value: '0' } });
    expect(document.activeElement).toBe(digits[1]);
    fireEvent.change(digits[1], { target: { value: '2' } });
    expect(document.activeElement).toBe(digits[2]);
    fireEvent.keyDown(digits[2], { key: 'Backspace' });
    expect(document.activeElement).toBe(digits[1]);
    expect(digits[1].value).toBe('');
    fireEvent.keyDown(digits[1], { key: 'ArrowLeft' });
    expect(document.activeElement).toBe(digits[0]);
    fireEvent.paste(digits[0], { clipboardData: { getData: () => '012345' } });
    expect(digits.map((input) => input.value).join('')).toBe('012345');
    fireEvent.change(digits[2], { target: { value: '9' } });
    expect(digits.map((input) => input.value).join('')).toBe('019345');
    fireEvent.paste(digits[0], { clipboardData: { getData: () => 'ABCDEF' } });
    expect(digits.map((input) => input.value).join('')).toBe('019345');
    fireEvent.submit(screen.getByRole('form', { name: 'Gabung room kuis' }));
    expect(send.mock.calls[0][2]).toEqual({ code: '019345' });
});

it.each(['AB12', 'AB12345678'])('preserves a prefilled legacy invitation %s', (code) => {
    render(<Quizzes quizzes={null} filters={{}} code={code} meta={meta} />);
    expect(screen.getByRole('textbox', { name: 'Kode room' }).value).toBe(code);
    fireEvent.submit(screen.getByRole('form', { name: 'Gabung room kuis' }));
    expect(send.mock.calls[0][2]).toEqual({ code });
});

it('shows a themed room-not-found dialog and preserves the code for correction', () => {
    page.props.errors = { room_not_found: 'Periksa kembali kode dari host, lalu coba lagi.' };
    render(<Quizzes quizzes={null} filters={{}} code="012345" meta={meta} />);
    expect(screen.getByRole('dialog', { name: 'Room tidak ditemukan' }).getAttribute('data-variant')).toBe('error');
    expect(screen.getByText('Periksa kembali kode dari host, lalu coba lagi.')).toBeTruthy();
    fireEvent.click(screen.getByRole('button', { name: 'Perbaiki kode' }));
    expect(screen.queryByRole('dialog')).toBeNull();
    expect(
        screen
            .getAllByRole('textbox')
            .map((input) => input.value)
            .join(''),
    ).toBe('012345');
    fireEvent.paste(screen.getByRole('textbox', { name: 'Digit 1 kode room' }), {
        clipboardData: { getData: () => '654321' },
    });
    fireEvent.submit(screen.getByRole('form', { name: 'Gabung room kuis' }));
    expect(send.mock.calls[0].slice(0, 3)).toEqual(['post', '/room/join', { code: '654321' }]);
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
    expect(payloadQuestions([{ prompt: 'Draf AI', options: ['A', 'B'], correct_option: 0 }])[0].question_type).toBe(
        'single',
    );
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

it('keeps essay keys in the editor and never requires an answer to save its preview', () => {
    render(<Editor quiz={null} meta={meta} />);
    fireEvent.click(screen.getByRole('button', { name: /Kelola soal/ }));
    fireEvent.change(screen.getByRole('combobox', { name: 'Tipe soal' }), { target: { value: 'essay' } });
    fireEvent.change(screen.getByRole('textbox', { name: 'Jawaban acuan' }), { target: { value: 'Krana\nKarena' } });
    expect(screen.queryByRole('textbox', { name: 'Pilihan A' })).toBeNull();
    fireEvent.click(screen.getByRole('button', { name: /Preview & simpan/ }));
    expect(screen.getByRole('textbox', { name: 'Jawaban esai' }).required).toBe(false);
    expect(send).not.toHaveBeenCalled();
    fireEvent.submit(screen.getByRole('button', { name: 'Simpan kuis' }).closest('form'));
    expect(send.mock.calls[0][2].questions[0]).toEqual(
        expect.objectContaining({ question_type: 'essay', accepted_answers: ['Krana', 'Karena'] }),
    );
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
    expect(screen.getByRole('heading', { name: 'Ruang belajar untuk mengenal bahasa dan budaya Bali.' })).toBeTruthy();
    expect(
        screen.getByRole('region', { name: 'Lebih mudah memulai, lebih banyak cara belajar.' }).querySelectorAll('h3'),
    ).toHaveLength(6);
    expect(screen.queryByText('Jelajahi Paribasa Bali')).toBeNull();
    expect(screen.getByText(/bukan testimoni pengguna nyata/)).toBeTruthy();
    unmount();
    render(<Privacy meta={meta} />);
    for (const id of [
        'ringkasan',
        'data',
        'ai',
        'cookie',
        'akses',
        'penyimpanan',
        'kendali',
        'keamanan',
        'pertanyaan',
        'kontak',
    ])
        expect(document.getElementById(id)).toBeTruthy();
    expect(screen.getByRole('link', { name: 'kerthajayasoftware@gmail.com' }).href).toBe(
        'mailto:kerthajayasoftware@gmail.com',
    );
});

it('keeps privacy topics reachable and opens data disclosures without a custom modal', () => {
    render(<Privacy meta={meta} />);
    const navigation = screen.getByRole('navigation', { name: 'Isi kebijakan privasi' });
    for (const link of within(navigation).getAllByRole('link')) {
        expect(document.querySelector(link.getAttribute('href'))).toBeTruthy();
    }
    const disclosure = screen.getByText('Profil anonim').closest('details');
    expect(disclosure.open).toBe(false);
    fireEvent.click(disclosure.querySelector('summary'));
    expect(disclosure.open).toBe(true);
    expect(within(disclosure).getByText(/Profil dan progres tersimpan di server/)).toBeTruthy();
    expect(screen.getByRole('link', { name: /Buka profil/ }).getAttribute('href')).toBe('/profil');
    expect(screen.getByText(/Hapus Akun Saya/)).toBeTruthy();
    expect(screen.getByText(/Analisis otomatis belum memiliki pengaturan nonaktif per profil/)).toBeTruthy();
});

it('resolves auth pages to the dedicated layout without public or mobile navigation', async () => {
    const { resolve } = await import('../../resources/js/react/resolve');
    const pageModule = await resolve('Auth/Form');
    render(pageModule.default.layout(<Auth mode="login" meta={meta} />));
    expect(screen.queryByRole('navigation')).toBeNull();
    expect(screen.getByRole('link', { name: 'Kembali ke beranda' })).toBeTruthy();
    expect(screen.getByRole('main').id).toBe('auth-content');
});

it.each(['login', 'register'])('keeps the Google %s button as a full-document OAuth link with its icon', (mode) => {
    render(<Auth mode={mode} meta={meta} />);
    const google = screen.getByRole('link', { name: 'Lanjutkan dengan Google' });
    expect(google.getAttribute('href')).toBe('/auth/google');
    expect(google.querySelector('svg')).not.toBeNull();
    expect(google.querySelector('svg').getAttribute('aria-hidden')).toBe('true');
});

it('shows a Google login error without hiding the sign-in form', () => {
    page.props.errors = { google: 'Login Google belum dikonfigurasi.' };
    render(<Auth mode="login" meta={meta} />);
    expect(screen.getByRole('alert').textContent).toContain('Login Google belum dikonfigurasi.');
    expect(screen.getByRole('textbox', { name: 'Email' })).toBeTruthy();
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

it('does not show an answer keyboard for multiple-choice lessons or rooms, including legacy auto-open flags', () => {
    const lesson = {
        title: 'Latihan aksara',
        content: 'Materi',
        path: { ...path, group: 'aksara' },
        exercise_prompt: 'Pilih aksara Ha',
        exercise_options: ['ᬳ', 'ᬦ'],
        keyboard_auto_open: true,
    };
    const { rerender } = render(<Lesson lesson={lesson} meta={meta} />);
    expect(screen.getAllByRole('radio')).toHaveLength(2);
    expect(screen.queryByText('Keyboard aksara Bali')).toBeNull();
    expect(screen.queryByRole('textbox', { name: 'Coretan latihan aksara' })).toBeNull();
    rerender(<Lesson lesson={lesson} progress={{ completed_at: '2026-10-27', points: 10 }} meta={meta} />);
    expect(screen.queryByText('Keyboard aksara Bali')).toBeNull();
    rerender(<Room room={{ ...room, question: { ...question, keyboard_auto_open: true } }} meta={meta} />);
    expect(screen.getAllByRole('radio')).toHaveLength(2);
    expect(screen.queryByText('Keyboard aksara Bali')).toBeNull();
});

it('submits all selected checkbox answers without a Balinese answer keyboard', () => {
    const lesson = {
        title: 'Uji',
        content: 'Materi',
        path,
        question_type: 'multiple',
        exercise_prompt: 'Pilih yang benar',
        exercise_options: ['Ha', 'Na', 'Ka'],
        keyboard_auto_open: true,
    };
    render(<Lesson lesson={lesson} meta={meta} />);
    expect(screen.getByRole('button', { name: 'Periksa jawaban' }).disabled).toBe(true);
    fireEvent.click(screen.getByRole('checkbox', { name: 'Ha' }));
    fireEvent.click(screen.getByRole('checkbox', { name: 'Ka' }));
    fireEvent.submit(screen.getByRole('button', { name: 'Periksa jawaban' }).closest('form'));
    expect(send.mock.calls[0][2].answer_indexes).toEqual([0, 2]);
    expect(screen.queryByText('Keyboard aksara Bali')).toBeNull();
});

it('uses the Balinese keyboard to edit the submitted essay answer instead of a scratch field', async () => {
    const lesson = {
        title: 'Uji',
        content: 'Materi',
        path,
        question_type: 'essay',
        exercise_prompt: 'Tulis Ka',
        exercise_options: [],
        keyboard_auto_open: true,
    };
    render(<Lesson lesson={lesson} meta={meta} />);
    const answer = screen.getByRole('textbox', { name: 'Jawaban esai' });
    expect(screen.queryByRole('radio')).toBeNull();
    const key = await screen.findByRole('button', { name: 'Ka', exact: true });
    fireEvent.click(key);
    expect(answer.value).toBe('ᬓ');
    expect(screen.queryByRole('textbox', { name: 'Coretan latihan aksara' })).toBeNull();
    fireEvent.submit(screen.getByRole('button', { name: 'Periksa jawaban' }).closest('form'));
    expect(send.mock.calls[0][2].answer_text).toBe('ᬓ');
});

it('reindexes every checkbox key when an option is removed', () => {
    const question = {
        ...blankQuestion(1),
        question_type: 'multiple',
        options: ['A', 'B', 'C', 'D'],
        correct_options: [0, 2, 3],
    };
    expect(withoutOption(question, 1).correct_options).toEqual([0, 1, 2]);
    expect(withoutOption(question, 2).correct_options).toEqual([0, 2]);
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
    expect(send.mock.calls[0][2]).toEqual({ question_id: 1, answer_index: 1, answer_indexes: [], answer_text: '' });
});

it('does not show a skeleton for an analysis that is only queued', async () => {
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({ ok: true, json: async () => ({ analysis: { status: 'queued' } }) }),
    );
    const { unmount } = render(<Analysis analysis={{ status: 'queued' }} />);
    expect(screen.queryByRole('status', { name: 'Menyiapkan analisis belajar…' })).toBeNull();
    expect(screen.getByText('Mengantre')).toBeTruthy();
    await act(async () => {});
    unmount();
});

it('stops the processing skeleton after twenty seconds and shows the completed analysis when it arrives', async () => {
    vi.useFakeTimers();
    const fetcher = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ analysis: { status: 'processing' } }) });
    vi.stubGlobal('fetch', fetcher);
    render(<Analysis analysis={{ status: 'processing' }} />);
    await act(async () => {});
    expect(screen.getByRole('status', { name: 'Menyiapkan analisis belajar…' })).toBeTruthy();
    await act(async () => vi.advanceTimersByTimeAsync(20000));
    expect(screen.queryByRole('status', { name: 'Menyiapkan analisis belajar…' })).toBeNull();
    expect(screen.getByText(/AI membutuhkan waktu lebih lama/)).toBeTruthy();
    fetcher.mockResolvedValue({
        ok: true,
        json: async () => ({
            analysis: { status: 'completed', summary: 'Belajar sedikit setiap hari.', recommendations: [] },
        }),
    });
    await act(async () => vi.advanceTimersByTimeAsync(5000));
    expect(screen.getByText('Belajar sedikit setiap hari.')).toBeTruthy();
    const calls = fetcher.mock.calls.length;
    await act(async () => vi.advanceTimersByTimeAsync(10000));
    expect(fetcher).toHaveBeenCalledTimes(calls);
});

it('bounds queued analysis polling and leaves learning available', async () => {
    vi.useFakeTimers();
    const fetcher = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ analysis: { status: 'queued' } }) });
    vi.stubGlobal('fetch', fetcher);
    render(<Analysis analysis={{ status: 'queued' }} />);
    await act(async () => {});
    await act(async () => vi.advanceTimersByTimeAsync(130000));
    expect(fetcher).toHaveBeenCalledTimes(24);
    expect(screen.getByRole('alert').textContent).toContain('Pemeriksaan otomatis dijeda');
    expect(screen.queryByRole('status', { name: 'Menyiapkan analisis belajar…' })).toBeNull();
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
            step={1}
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

it('groups admin navigation and keeps module preview selected with a usable mobile menu', () => {
    page.component = 'Admin/Preview';
    page.props.viewer = { ...page.props.viewer, mode: 'account', isAdmin: true };
    page.props.links = {
        ...page.props.links,
        'admin.index': '/admin',
        'admin.analytics.index': '/admin/analitik',
        'admin.lessons.index': '/admin/pelajaran',
        'admin.learning-paths.index': '/admin/jalur',
        'admin.ai.index': '/admin/ai',
        'admin.audio.index': '/admin/audio',
    };
    page.props.navigation.logout = '/keluar';
    const { unmount } = render(
        <AdminLayout>
            <h1>Preview materi</h1>
        </AdminLayout>,
    );
    expect(screen.getByRole('link', { name: 'Modul pembelajaran' }).getAttribute('aria-current')).toBe('page');
    expect(screen.getByRole('main').classList.contains('admin-workspace')).toBe(true);
    fireEvent.click(screen.getByRole('button', { name: 'Buka menu admin' }));
    const drawer = within(screen.getByRole('dialog', { name: 'Menu admin' }));
    expect(drawer.getByRole('navigation', { name: 'Menu pengelolaan' })).toBeTruthy();
    const statisticsLink = drawer.getByRole('link', { name: 'Statistik & aktivitas' });
    statisticsLink.addEventListener('click', (event) => event.preventDefault());
    fireEvent.click(statisticsLink);
    expect(screen.queryByRole('dialog')).toBeNull();
    unmount();
    delete page.component;
});

it('shows absolute weekly changes without inventing percentages from zero baselines', () => {
    render(
        <AdminWeekly
            weekly={{
                completions: { current: 5, previous: 0 },
                answers: { current: 2, previous: 4 },
                users: { current: 0, previous: 0 },
            }}
        />,
    );
    expect(screen.getByText('+5')).toBeTruthy();
    expect(screen.getByText('-2')).toBeTruthy();
    expect(screen.queryByText(/Infinity|NaN/)).toBeNull();
    expect(screen.getAllByText(/dibanding 7 hari sebelumnya/)).toHaveLength(3);
});

it('updates online visitor counts from the live endpoint without exposing identities', async () => {
    page.props.links['admin.analytics.online'] = '/admin/analitik/online';
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({ ok: true, json: async () => ({ total: 7, accounts: 4, anonymous: 3 }) }),
    );
    render(<OnlineVisitors />);
    await waitFor(() => expect(screen.getByText('7')).toBeTruthy());
    expect(screen.getByText('4')).toBeTruthy();
    expect(screen.getByText('3')).toBeTruthy();
    expect(screen.getByText('Sinyal aktivitas dalam 2 menit terakhir')).toBeTruthy();
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

it.each(['anonymous', 'account'])('requires the exact typed phrase before deleting the %s profile', (mode) => {
    page.props.viewer = { ...page.props.viewer, mode };
    render(<Profile paths={[]} interests={[]} completedLessons={0} totalLessons={0} avatars={[]} meta={meta} />);
    fireEvent.click(screen.getByRole('button', { name: 'Hapus akun saya' }));
    const dialog = within(screen.getByRole('dialog', { name: 'Hapus akun ini?' }));
    expect(screen.getByRole('dialog').getAttribute('data-variant')).toBe('error');
    const confirmation = dialog.getByRole('textbox', { name: 'Konfirmasi penghapusan akun' });
    const remove = dialog.getByRole('button', { name: 'Hapus permanen' });
    expect(remove.disabled).toBe(true);
    fireEvent.change(confirmation, { target: { value: 'hapus akun saya' } });
    fireEvent.click(remove);
    expect(send).not.toHaveBeenCalled();
    fireEvent.change(confirmation, { target: { value: 'Hapus Akun Saya' } });
    expect(remove.disabled).toBe(false);
    fireEvent.submit(remove.closest('form'));
    expect(send.mock.calls[0].slice(0, 3)).toEqual(['delete', '/profil', { confirmation: 'Hapus Akun Saya' }]);
});

it('can cancel account deletion without sending a request and clears the phrase when reopened', () => {
    render(<Profile paths={[]} interests={[]} completedLessons={0} totalLessons={0} avatars={[]} meta={meta} />);
    fireEvent.click(screen.getByRole('button', { name: 'Hapus akun saya' }));
    fireEvent.change(screen.getByRole('textbox', { name: 'Konfirmasi penghapusan akun' }), {
        target: { value: 'Hapus Akun Saya' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'Batal' }));
    expect(screen.queryByRole('dialog')).toBeNull();
    expect(send).not.toHaveBeenCalled();
    fireEvent.click(screen.getByRole('button', { name: 'Hapus akun saya' }));
    expect(screen.getByRole('textbox', { name: 'Konfirmasi penghapusan akun' }).value).toBe('');
    expect(screen.getByRole('button', { name: 'Hapus permanen' }).disabled).toBe(true);
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

it.each(['account', 'anonymous'])(
    'takes an existing %s identity directly to learning without an interview picker',
    (mode) => {
        page.props.viewer = { ...page.props.viewer, mode };
        render(<Home paths={[path]} selectedIds={[1]} hasInterview={false} meta={meta} />);
        expect(screen.getByRole('link', { name: 'Lanjut belajar', exact: true }).getAttribute('href')).toBe('/belajar');
        expect(screen.queryByRole('dialog')).toBeNull();
        expect(screen.queryByText('Mulai belajar gratis')).toBeNull();
    },
);

it.each(['account', 'anonymous'])('keeps the %s mobile home focused without repeating catalog or analysis', (mode) => {
    page.props.viewer = { ...page.props.viewer, mode };
    render(<Home paths={[path]} selectedIds={[1]} analysis={{ status: 'queued' }} meta={meta} />);
    const home = within(screen.getByRole('region', { name: 'Beranda belajarmu' }));
    expect(home.queryByText('Materi pilihanmu')).toBeNull();
    expect(home.queryByText('Analisis belajarmu')).toBeNull();
    expect(home.queryByText('Belum ada riwayat belajar')).toBeNull();
    expect(home.getByRole('link', { name: /Mulai satu pelajaran/ }).getAttribute('href')).toBe('/belajar');
    fireEvent.click(home.getByRole('button', { name: 'Sapa maskot belajarmu' }));
    expect(home.getByRole('status').textContent).toContain('Rahajeng!');
});

it('opens the actual latest lesson from the mobile home', () => {
    render(
        <Home recent={{ title: 'Sapaan pertama', pathTitle: 'Bahasa Bali', url: '/pelajaran/sapaan' }} meta={meta} />,
    );
    const home = within(screen.getByRole('region', { name: 'Beranda belajarmu' }));
    expect(home.getByRole('heading', { name: 'Sapaan pertama' })).toBeTruthy();
    expect(home.getByRole('link', { name: /Buka pelajaran terakhir/ }).getAttribute('href')).toBe('/pelajaran/sapaan');
});

it('renders mandatory interview as a full page with four named steps and no dismiss control', () => {
    page.props.viewer = null;
    render(<Interview paths={[path]} answers={{}} step={1} roles={{}} meta={meta} />);
    expect(screen.queryByRole('dialog')).toBeNull();
    expect(screen.getByRole('region', { name: 'Perkenalan awal' })).toBeTruthy();
    expect(screen.getByRole('list', { name: 'Langkah 1 dari 4' }).children).toHaveLength(4);
    expect(screen.getByRole('button', { name: 'Pilih anonim' })).toBeTruthy();
    expect(screen.queryByRole('checkbox')).toBeNull();
    expect(screen.queryByRole('button', { name: 'Tutup' })).toBeNull();
    expect(screen.getByRole('link', { name: 'Sudah punya akun? Masuk' }).getAttribute('href')).toBe('/login');
});

it('uses the full-page interview layout without public navigation or a close-to-home action', async () => {
    const { resolve } = await import('../../resources/js/react/resolve');
    const pageModule = await resolve('Learning/Interview');
    render(pageModule.default.layout(<Interview paths={[path]} answers={{}} step={1} roles={{}} meta={meta} />));
    expect(screen.getByRole('main').id).toBe('auth-content');
    expect(screen.queryByRole('dialog')).toBeNull();
    expect(screen.queryByRole('navigation')).toBeNull();
    expect(screen.queryByRole('link', { name: 'Kembali ke beranda' })).toBeNull();
    expect(screen.getByRole('region', { name: 'Perkenalan awal' }).classList.contains('onboarding-step')).toBe(true);
});

it('renders an accessible FAQ accordion with the first answer available immediately', () => {
    render(<Faq />);
    const first = screen.getByText('Apa itu ARTASWAHA?').closest('details');
    const account = screen.getByText('Apakah harus membuat akun?').closest('summary');
    expect(first.open).toBe(true);
    expect(account.closest('details').open).toBe(false);
    fireEvent.click(account);
    expect(account.closest('details').open).toBe(true);
    expect(screen.getByRole('link', { name: 'Hubungi kami' }).getAttribute('href')).toBe(
        'mailto:kerthajayasoftware@gmail.com',
    );
});

it('keeps the saved AI summary visible while updating and labels the first factual summary honestly', () => {
    const { rerender } = render(
        <Analysis
            poll={false}
            analysis={{ status: 'processing', source: 'ai', summary: 'Hasil AI terakhir.', recommendations: [] }}
        />,
    );
    expect(screen.getByText('Hasil AI terakhir.')).toBeTruthy();
    expect(screen.getByText(/Hasil AI terakhir tetap ditampilkan/)).toBeTruthy();
    expect(screen.queryByRole('status', { name: 'Menyiapkan analisis belajar…' })).toBeNull();
    rerender(
        <Analysis
            poll={false}
            analysis={{ status: 'queued', source: 'initial', summary: 'Tercatat 0 hari aktif.', recommendations: [] }}
        />,
    );
    expect(screen.getByText('Tercatat 0 hari aktif.')).toBeTruthy();
    expect(screen.getByText(/belum merupakan hasil AI/)).toBeTruthy();
});

it('uses one account entry to login before choosing registration or Google', () => {
    page.props.viewer = null;
    render(<Interview paths={[path]} answers={{}} step={1} roles={{}} meta={meta} />);
    expect(screen.getByRole('link', { name: 'Masuk / daftar' }).getAttribute('href')).toBe('/login');
    expect(screen.queryByRole('link', { name: 'Lanjutkan dengan Google' })).toBeNull();
    expect(screen.queryByRole('button', { name: 'Daftar akun baru' })).toBeNull();
    expect(send).not.toHaveBeenCalled();
});

it('shows material examples before a module quiz and submits only the current question index', () => {
    const lesson = {
        id: 11,
        title: 'Ha sampai Wa',
        path,
        content:
            '## Kenali aksara\nAmati bentuknya.\n\n| Bacaan | Aksara | Perhatikan |\n| Ha | ᬳ | Vokal a. |\n\n## Coba kata\nBaca kata pendek.\n\n## Sumber belajar\nhttps://www.unicode.org/charts/PDF/U1B00.pdf',
        answerUrl: '/pelajaran/uji/latihan',
    };
    const quiz = {
        total: 10,
        index: 0,
        question: { section: 0, question_type: 'single', exercise_prompt: 'Pilih Ha', exercise_options: ['ᬳ', 'ᬦ'] },
    };
    render(<Lesson lesson={lesson} quiz={quiz} meta={meta} />);
    expect(screen.getByRole('navigation', { name: 'Bagian pelajaran' })).toBeTruthy();
    expect(screen.getByRole('figure')).toBeTruthy();
    expect(screen.queryByRole('radio')).toBeNull();
    fireEvent.click(screen.getByRole('button', { name: 'Coba latihan bagian ini' }));
    fireEvent.click(screen.getByRole('radio', { name: 'ᬳ' }));
    fireEvent.submit(screen.getByRole('button', { name: 'Periksa jawaban' }).closest('form'));
    expect(send.mock.calls[0][1]).toBe(lesson.answerUrl);
    expect(send.mock.calls[0][2]).toMatchObject({ answer_index: 0, question_index: 0 });
    fireEvent.click(screen.getByRole('button', { name: 'Lihat materi' }));
    expect(screen.queryByRole('radio')).toBeNull();
});

it('pauses between sections for reading and allows retry feedback to appear again', () => {
    const lesson = {
        id: 11,
        title: 'Latihan',
        path,
        content: '## Pertama\nIsi satu.\n\n## Kedua\nIsi dua.',
        answerUrl: '/latihan',
    };
    const first = {
        section: 0,
        question_type: 'single',
        exercise_prompt: 'Pilih satu',
        exercise_options: ['Ha', 'Na'],
    };
    const quiz = { total: 10, index: 0, question: first };
    const { rerender } = render(<Lesson lesson={lesson} quiz={quiz} meta={meta} />);
    fireEvent.click(screen.getByRole('button', { name: 'Coba latihan bagian ini' }));
    const wrong = { correct: false, question_index: 0, explanation: 'Perhatikan Ha.', correct_answer: 'Ha' };
    rerender(<Lesson lesson={lesson} quiz={{ ...quiz, feedback: wrong }} meta={meta} />);
    expect(screen.getByText('Perhatikan Ha.')).toBeTruthy();
    fireEvent.click(screen.getByRole('button', { name: 'Coba lagi' }));
    fireEvent.click(screen.getByRole('radio', { name: 'Na' }));
    fireEvent.submit(screen.getByRole('button', { name: 'Periksa jawaban' }).closest('form'));
    expect(screen.getByText('Perhatikan Ha.')).toBeTruthy();
    const correct = { correct: true, question_index: 0, explanation: 'Benar Ha.', correct_answer: 'Ha' };
    rerender(
        <Lesson
            lesson={lesson}
            quiz={{ ...quiz, index: 1, question: { ...first, section: 1 }, feedback: correct }}
            meta={meta}
        />,
    );
    fireEvent.click(screen.getByRole('button', { name: 'Baca bagian berikutnya' }));
    expect(screen.getByRole('heading', { name: 'Kedua' })).toBeTruthy();
    expect(screen.queryByRole('radio')).toBeNull();
});

it('provides Bali digits and punctuation in the keyboard without losing the essay text', () => {
    render(<Keyboard autoOpen />);
    fireEvent.click(screen.getByRole('button', { name: 'angka', exact: true }));
    fireEvent.click(screen.getByRole('button', { name: 'Carik siki', exact: true }));
    fireEvent.click(screen.getByRole('button', { name: '3', exact: true }));
    fireEvent.click(screen.getByRole('button', { name: 'Carik siki', exact: true }));
    expect(screen.getByRole('textbox').value).toBe('᭞᭓᭞');
});

it('shows locked modules without a navigable link', () => {
    render(
        <LearningPath
            path={{ title: 'Wreastra', group: 'aksara', progress: 0 }}
            lessons={{
                total: 2,
                from: 1,
                data: [
                    { id: 1, title: 'Modul pertama', url: '/pelajaran/pertama', progress: 0, locked: false },
                    { id: 2, title: 'Modul kedua', url: null, progress: 0, locked: true },
                ],
                links: [],
            }}
        />,
    );
    expect(screen.getByRole('link', { name: /Modul pertama/ }).getAttribute('href')).toBe('/pelajaran/pertama');
    expect(screen.queryByRole('link', { name: /Modul kedua/ })).toBeNull();
    expect(screen.getByText('Terkunci · selesaikan modul sebelumnya')).toBeTruthy();
    expect(screen.getByText('Modul kedua').closest('[aria-disabled="true"]')).toBeTruthy();
});
