import { afterEach, expect, it, vi } from 'vitest';
import { cleanup, render, screen, waitFor } from '@testing-library/react';
import { domAnimation, LazyMotion } from 'motion/react';
import QuizTransition from '../../resources/js/react/Components/QuizTransition';

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
});

it('keeps the outgoing quiz visible until its exit finishes, then shows the next question', async () => {
    vi.stubGlobal('matchMedia', () => ({ matches: false, addEventListener() {}, removeEventListener() {} }));
    const content = (key, text) => (
        <LazyMotion features={domAnimation} strict>
            <QuizTransition stateKey={key}><p>{text}</p></QuizTransition>
        </LazyMotion>
    );
    const { rerender } = render(content('feedback', 'Jawaban tersimpan'));
    rerender(content('next', 'Pertanyaan berikutnya'));
    expect(screen.getByText('Jawaban tersimpan')).toBeTruthy();
    expect(screen.queryByText('Pertanyaan berikutnya')).toBeNull();
    await waitFor(() => expect(screen.getByText('Pertanyaan berikutnya')).toBeTruthy());
    expect(screen.queryByText('Jawaban tersimpan')).toBeNull();
});
