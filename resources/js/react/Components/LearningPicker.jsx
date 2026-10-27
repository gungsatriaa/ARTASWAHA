import { useState } from 'react';
import { Link } from '@inertiajs/react';
import Dialog from './Dialog';
import { Button, CategoryIcon, EmptyState, buttonClass } from './ui';
import { secondaryClass } from './forms';

export default function LearningPicker({ paths, onClose }) {
    const [step, setStep] = useState(1),
        [group, setGroup] = useState(''),
        [chosen, setChosen] = useState(null);
    const available = paths.filter((path) => path.group === group);
    return (
        <Dialog open onClose={onClose} title="Pilih langkah belajarmu" id="choose-learning">
            <div className="mb-5 flex items-center gap-3" aria-label={`Langkah ${step} dari 2`}>
                {['Jenis materi', 'Pilih materi'].map((label, index) => (
                    <div key={label} className="flex-1">
                        <div className={`mb-2 h-1 rounded-full ${index < step ? 'bg-accent' : 'bg-white/10'}`} />
                        <p
                            className={`text-xs font-semibold ${index + 1 === step ? 'text-accent' : 'text-foreground/40'}`}
                        >
                            {index + 1}. {label}
                        </p>
                    </div>
                ))}
            </div>
            {!paths.length ? (
                <EmptyState title="Materi sedang disiapkan">Silakan kembali beberapa saat lagi.</EmptyState>
            ) : (
                <>
                    <h3 className="font-display text-xl font-bold">
                        {step === 1
                            ? 'Apa yang ingin kamu jelajahi?'
                            : group === 'aksara'
                              ? 'Pilih materi Aksara Bali'
                              : 'Pilih materi Paribasa Bali'}
                    </h3>
                    <p className="mt-2 text-sm leading-7 text-foreground/50">
                        {step === 1
                            ? 'Mulai dari bahasa sehari-hari atau kenali bentuk aksaranya.'
                            : 'Pilih satu materi untuk mulai belajar. Kamu bisa kembali dan mencoba yang lain.'}
                    </p>
                    <fieldset className="my-5 grid gap-3">
                        <legend className="sr-only">{step === 1 ? 'Jenis materi' : 'Materi belajar'}</legend>
                        {step === 1
                            ? [
                                  ['paribasa', 'Paribasa Bali', 'Teka-teki, parikan, dan ungkapan.'],
                                  ['aksara', 'Aksara Bali', 'Huruf, tanda, gantungan, dan angka.'],
                              ].map(([value, title, description]) => (
                                  <label
                                      key={value}
                                      className={`flex cursor-pointer items-center gap-3 rounded-2xl focus-within:ring-2 focus-within:ring-accent bg-background p-4 ring-1 ${group === value ? 'ring-accent' : 'ring-transparent'}`}
                                  >
                                      <input
                                          type="radio"
                                          name="learning-group"
                                          checked={group === value}
                                          onChange={() => {
                                              setGroup(value);
                                              setChosen(null);
                                          }}
                                          className="sr-only"
                                      />
                                      <CategoryIcon group={value} />
                                      <span className="min-w-0 flex-1">
                                          <span className="block font-bold">{title}</span>
                                          <span className="mt-1 block text-xs leading-6 text-foreground/50">
                                              {description}
                                          </span>
                                      </span>
                                      <span
                                          aria-hidden="true"
                                          className={`size-4 shrink-0 rounded-full ring-2 ${group === value ? 'bg-accent ring-accent' : 'ring-white/20'}`}
                                      />
                                  </label>
                              ))
                            : available.map((path) => (
                                  <label
                                      key={path.id}
                                      className={`flex cursor-pointer items-center gap-3 rounded-2xl focus-within:ring-2 focus-within:ring-accent bg-background p-4 ring-1 ${chosen?.id === path.id ? 'ring-accent' : 'ring-transparent'}`}
                                  >
                                      <input
                                          type="radio"
                                          name="learning-path"
                                          checked={chosen?.id === path.id}
                                          onChange={() => setChosen(path)}
                                          className="size-4 shrink-0 accent-[var(--color-accent)]"
                                      />
                                      <span className="min-w-0">
                                          <span className="block font-bold">{path.title}</span>
                                          <span className="mt-1 block text-xs leading-6 text-foreground/50">
                                              {path.description}
                                          </span>
                                      </span>
                                  </label>
                              ))}
                    </fieldset>
                    {step === 2 && !available.length && (
                        <p className="mb-5 text-sm text-foreground/55">
                            Materi untuk kategori ini sedang disiapkan. Coba kategori lainnya.
                        </p>
                    )}
                    <div className="flex items-center justify-between gap-3">
                        {step === 2 ? (
                            <button type="button" onClick={() => setStep(1)} className={secondaryClass}>
                                ← Kembali
                            </button>
                        ) : (
                            <span className="text-xs text-foreground/40">Langkah 1 dari 2</span>
                        )}
                        {step === 1 ? (
                            <Button disabled={!group} onClick={() => setStep(2)}>
                                Lanjutkan →
                            </Button>
                        ) : chosen ? (
                            <Link href={chosen.url} onClick={onClose} className={buttonClass}>
                                Mulai belajar →
                            </Link>
                        ) : (
                            <Button disabled>Mulai belajar →</Button>
                        )}
                    </div>
                </>
            )}
        </Dialog>
    );
}
