import Skeleton from './Skeleton';
import { lazy, Suspense, useState } from 'react';
import { secondaryClass } from './forms';
import Icon from './Icon';
const Keyboard = lazy(() => import('./Keyboard'));
export default function LazyKeyboard(props) {
    const [visible, setVisible] = useState(Boolean(props.autoOpen));
    return visible ? (
        <Suspense fallback={<Skeleton label="Menyiapkan keyboard…" compact />}>
            <Keyboard {...props} autoOpen />
        </Suspense>
    ) : (
        <button type="button" className={`${secondaryClass} my-4`} onClick={() => setVisible(true)}>
            <Icon name="aksara" className="size-5 text-accent" />
            Keyboard aksara Bali
        </button>
    );
}
