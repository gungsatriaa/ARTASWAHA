import Skeleton from './Skeleton';
import { lazy, Suspense, useState } from 'react';
import { secondaryClass } from './forms';
const Keyboard = lazy(() => import('./Keyboard'));
export default function LazyKeyboard(props) {
    const [visible, setVisible] = useState(Boolean(props.autoOpen));
    return visible ? (
        <Suspense fallback={<Skeleton label="Menyiapkan keyboard…" compact />}>
            <Keyboard {...props} autoOpen />
        </Suspense>
    ) : (
        <button type="button" className={`${secondaryClass} my-4`} onClick={() => setVisible(true)}>
            Keyboard aksara Bali
        </button>
    );
}
