export default function GrafikKeterlambatan({
    data,
    judul = "📈 Tren",
    warna = "indigo",
    filter = null,
}) {
    const list = Array.isArray(data) ? data : [];
    const maxJumlah = Math.max(...list.map((d) => d.jumlah), 1);
    const gridLines = [0, Math.round(maxJumlah / 2), maxJumlah];

    const barClass =
        warna === "orange"
            ? "bg-gradient-to-t from-orange-600 to-orange-400 hover:from-orange-700 hover:to-orange-500"
            : "bg-gradient-to-t from-indigo-600 to-indigo-400 hover:from-indigo-700 hover:to-indigo-500";

    return (
        <div className="h-full rounded-xl bg-white p-6 shadow">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 className="text-base font-semibold text-gray-800">
                    {judul}
                </h3>
                {filter && (
                    <div className="flex flex-wrap items-center gap-2">
                        <select
                            value={filter.kelas ?? ""}
                            onChange={(e) =>
                                filter.onChange(
                                    e.target.value,
                                    filter.jurusan ?? "",
                                )
                            }
                            className="rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Kelas</option>
                            {filter.kelasOptions.map((k) => (
                                <option key={k} value={k}>
                                    {k}
                                </option>
                            ))}
                        </select>
                        <select
                            value={filter.jurusan ?? ""}
                            onChange={(e) =>
                                filter.onChange(
                                    filter.kelas ?? "",
                                    e.target.value,
                                )
                            }
                            className="rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Jurusan</option>
                            {filter.jurusanOptions.map((j) => (
                                <option key={j} value={j}>
                                    {j}
                                </option>
                            ))}
                        </select>
                        {(filter.kelas || filter.jurusan) && (
                            <button
                                onClick={() => filter.onChange("", "")}
                                className="rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-200"
                            >
                                ↺
                            </button>
                        )}
                    </div>
                )}
            </div>

            {list.length === 0 ? (
                <p className="py-16 text-center text-sm text-gray-400">
                    Belum ada data pada rentang ini
                </p>
            ) : (
                <div className="relative h-64">
                    {gridLines.map((line) => (
                        <div
                            key={line}
                            className="absolute left-0 right-0 border-t border-dashed border-gray-200"
                            style={{ bottom: `${(line / maxJumlah) * 100}%` }}
                        >
                            <span className="absolute -left-1 -top-2 text-[10px] text-gray-400">
                                {line}
                            </span>
                        </div>
                    ))}
                    <div className="absolute inset-0 flex items-end gap-2 pl-8">
                        {list.map((d, idx) => {
                            const height =
                                maxJumlah > 0
                                    ? (d.jumlah / maxJumlah) * 100
                                    : 0;
                            return (
                                <div
                                    key={idx}
                                    className="flex h-full flex-1 flex-col items-center"
                                >
                                    <div className="relative flex w-full flex-1 items-end">
                                        <div
                                            className={`w-full rounded-t-md transition-all ${barClass}`}
                                            style={{
                                                height: `${height}%`,
                                                minHeight:
                                                    d.jumlah > 0 ? "4px" : "0",
                                            }}
                                            title={`${d.title ?? d.label}: ${d.jumlah} kasus`}
                                        >
                                            {d.jumlah > 0 && (
                                                <span className="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-semibold text-gray-600">
                                                    {d.jumlah}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <span
                                        className="mt-2 max-w-full truncate text-[10px] font-medium text-gray-500"
                                        title={d.label}
                                    >
                                        {d.label}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
