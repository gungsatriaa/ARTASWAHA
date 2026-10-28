import { AnimatePresence, useReducedMotion } from 'motion/react';
import * as m from 'motion/react-m';

export default function QuizTransition({ stateKey, children }) {
    const reduced = useReducedMotion();
    return (
        <div className="min-w-0">
            <AnimatePresence initial={false} mode="wait">
                <m.div
                    key={stateKey}
                    initial={{ opacity: 0, y: reduced ? 0 : 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: reduced ? 0 : -6 }}
                    transition={{ duration: reduced ? 0 : 0.18, ease: 'easeOut' }}
                    className="min-w-0 space-y-5"
                >
                    {children}
                </m.div>
            </AnimatePresence>
        </div>
    );
}
