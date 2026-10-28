import { Fragment } from 'react';

export function lessonSections(content = '') {
    if (!content.trim()) return [];
    if (!/^## /m.test(content)) return [{ title: 'Materi pelajaran', body: content }];
    return content
        .split(/^## /m)
        .filter((part) => part.trim())
        .map((part) => {
            const [title, ...lines] = part.split('\n');
            return { title: title.trim(), body: lines.join('\n').trim() };
        });
}

export function AksaraText({ children }) {
    return String(children ?? '')
        .split(/([\u1B00-\u1B7F◌]+)/u)
        .map((part, index) =>
            /[\u1B00-\u1B7F]/u.test(part) ? (
                <span key={index} lang="ban" className="font-balinese text-[1.45em] leading-[2] text-foreground">
                    {part}
                </span>
            ) : (
                <Fragment key={index}>{part}</Fragment>
            ),
        );
}

export default function LessonMaterial({ content }) {
    const blocks = content.trim().split(/\n\s*\n/);
    return (
        <div className="space-y-6">
            {blocks.map((block, index) => {
                if (block.startsWith('|')) {
                    const rows = block
                        .split('\n')
                        .filter((line) => line.startsWith('|'))
                        .map((line) =>
                            line
                                .split('|')
                                .slice(1, -1)
                                .map((cell) => cell.trim()),
                        );
                    return (
                        <div key={index} className="grid gap-3 sm:grid-cols-2">
                            {rows
                                .slice(1)
                                .filter((row) => !row.every((cell) => /^[-: ]+$/.test(cell)))
                                .map((row, rowIndex) => (
                                    <figure
                                        key={rowIndex}
                                        className="flex min-w-0 flex-col items-center rounded-3xl bg-background px-5 py-6 text-center"
                                    >
                                        <span
                                            lang="ban"
                                            className="block max-w-full break-words py-3 font-balinese text-4xl leading-[2.1] text-accent sm:text-5xl"
                                        >
                                            {row[1]}
                                        </span>
                                        <figcaption className="mt-2 font-semibold text-foreground">{row[0]}</figcaption>
                                        {row[2] && (
                                            <p className="mt-2 max-w-[28ch] text-sm leading-7 text-foreground/65">
                                                <AksaraText>{row[2]}</AksaraText>
                                            </p>
                                        )}
                                    </figure>
                                ))}
                        </div>
                    );
                }
                return (
                    <p
                        key={index}
                        className="max-w-[65ch] whitespace-pre-line break-words text-base leading-[1.95] text-foreground/80 sm:text-lg"
                    >
                        {block.split(/(https?:\/\/[^\s]+)/u).map((part, partIndex) =>
                            /^https?:\/\//.test(part) ? (
                                <a
                                    key={partIndex}
                                    href={part}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="break-all text-sm font-semibold text-accent underline underline-offset-4"
                                >
                                    Buka sumber rujukan
                                </a>
                            ) : (
                                <AksaraText key={partIndex}>{part}</AksaraText>
                            ),
                        )}
                    </p>
                );
            })}
        </div>
    );
}
