import { LazyMotion, MotionConfig } from 'motion/react';

const loadFeatures = () => import('./motion-features').then(module => module.default);

export default function MotionProvider({ children }) {
    return <MotionConfig reducedMotion="user"><LazyMotion features={loadFeatures} strict>{children}</LazyMotion></MotionConfig>;
}
