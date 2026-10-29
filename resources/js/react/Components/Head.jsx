import { Children, Fragment, cloneElement, createContext, isValidElement, useContext, useEffect, useRef } from 'react';
import { title as formatTitle } from '../resolve';

const HeadContext = createContext(null);
const owners = new WeakMap();

/** registry is a request-local Map<head-key, ReactElement>, serialized after SSR. */
export function HeadProvider({ registry, children }) {
    return <HeadContext.Provider value={registry}>{children}</HeadContext.Provider>;
}

function collect(children, entries) {
    Children.forEach(children, (child) => {
        if (!isValidElement(child)) return;
        if (child.type === Fragment) {
            collect(child.props.children, entries);
            return;
        }
        if (typeof child.type !== 'string') return;

        const { 'head-key': headKey, ...props } = child.props;
        const key = String(
            headKey ??
                (child.type === 'title'
                    ? 'title'
                    : `${child.type}:${props.name ?? props.property ?? props.rel ?? entries.size}`),
        );
        if (child.type === 'title') props.children = formatTitle(String(props.children ?? ''));
        if (child.type === 'script' && props.type === 'application/ld+json' && props.dangerouslySetInnerHTML) {
            props.dangerouslySetInnerHTML = {
                __html: props.dangerouslySetInnerHTML.__html.replace(/</g, '\\u003c'),
            };
        }
        entries.set(key, cloneElement(child, { ...props, 'head-key': undefined, 'data-react-head': key }));
    });
}

export default function Head({ title, children }) {
    const registry = useContext(HeadContext);
    const owner = useRef({});
    const entries = new Map();
    if (title !== undefined && title !== null) collect(<title>{title}</title>, entries);
    collect(children, entries);
    if (registry) entries.forEach((element, key) => registry.set(key, element));

    useEffect(() => {
        // Claim server/fallback metadata once; later Head instances retain their own tags.
        document.head.querySelectorAll('[data-react-head]').forEach((node) => {
            if (!owners.has(node)) node.remove();
        });

        const nodes = [];
        entries.forEach((element, key) => {
            const node = document.createElement(element.type);
            Object.entries(element.props).forEach(([name, value]) => {
                if (name === 'children' || name === 'dangerouslySetInnerHTML' || value == null || name === 'head-key') {
                    return;
                }
                const attribute = name === 'charSet' ? 'charset' : name === 'className' ? 'class' : name;
                node.setAttribute(attribute, String(value));
            });
            node.textContent = element.props.dangerouslySetInnerHTML?.__html ?? element.props.children ?? '';
            document.head.querySelectorAll('[data-react-head]').forEach((existing) => {
                if (existing.getAttribute('data-react-head') === key) existing.remove();
            });
            document.head.appendChild(node);
            owners.set(node, owner.current);
            nodes.push(node);
        });

        return () => {
            nodes.forEach((node) => {
                if (owners.get(node) === owner.current) node.remove();
            });
        };
    }, [children, title]);

    return null;
}
