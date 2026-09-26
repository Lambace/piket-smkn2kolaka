export default function GrafikKeterlambatan({
    data,
    judul = "📈 Tren",
    warna = "indigo",
    filter = null,
}) {
    const list = Array.isArray(data) ? data : [];
    const maxJumlah = Math.max(...list.map((d) => d.jumlah), 1);
    const gridLines = [0, Math.round(maxJumlah / 2), maxJumlah];

    // ✅ PALET WARNA SINKRON DENGAN DONUT CHART
    // Mapping berdasarkan label jurusan/kelas
    const paletWarna = {
        TSM: "from-indigo-600 to-indigo-400 hover:from-indigo-700 hover:to-indigo-500",
        TPM: "from-red-600 to-red-400 hover:from-red-700 hover:to-red-500",
        TKR: "from-amber-500 to-amber-300 hover:from-amber-600 hover:to-amber-400",
        TKJ: "from-emerald-600 to-emerald-400 hover:from-emerald-700 hover:to-emerald-500",
        TAB: "from-violet-600 to-violet-400 hover:from-violet-700 hover:to-violet-500",
        ATU: "from-pink-500 to-pink-300 hover:from-pink-600 hover:to-pink-400",
        DPIB: "from-teal-600 to-teal-400 hover:from-teal-700 hover:to-teal-500",
        ATPH: "from-orange-500 to-orange-300 hover:from-orange-600 hover:to-orange-400",
    };

    // Fallback warna default jika tidak ada di palet
    const defaultWarna =
        warna === "orange"
            ? "from-orange-600 to-orange-400 hover:from-orange-700 hover:to-orange-500"
            : "from-indigo-600 to-indigo-400 hover:from-indigo-700 hover:to-indigo-500";

    // Fungsi untuk mendapatkan warna berdasarkan label
    const getWarnaBar = (label) => {
        if (!label) return defaultWarna;

        // Cek exact match
        if (paletWarna[label]) return paletWarna[label];

        // Cek partial match (misal "XI TSM" mengandung "TSM")
        for (const [key, warna] of Object.entries(paletWarna)) {
            if (label.toUpperCase().includes(key.toUpperCase())) {
                return warna;
            }
        }

        return defaultWarna;
    };

    return (
        <div className="h-full rounded-xl bg-white p-4 sm:p-6 shadow">
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
                <div className="relative h-64 sm:h-72">
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

                    {/* ✅ CONTAINER DENGAN SCROLL HORIZONTAL DI MOBILE */}
                    <div className="absolute inset-0 flex items-end gap-1 sm:gap-2 pl-6 sm:pl-8 overflow-x-auto pb-6">
                        {list.map((d, idx) => {
                            const height =
                                maxJumlah > 0
                                    ? (d.jumlah / maxJumlah) * 100
                                    : 0;

                            // ✅ AMBIL WARNA BERDASARKAN LABEL
                            const barWarna = getWarnaBar(d.label);

                            return (
                                <div
                                    key={idx}
                                    className="flex h-full flex-col items-center min-w-[40px] sm:min-w-[50px] flex-shrink-0"
                                >
                                    <div className="relative flex w-full flex-1 items-end">
                                        <div
                                            className={`w-full rounded-t-md bg-gradient-to-t transition-all ${barWarna}`}
                                            style={{
                                                height: `${height}%`,
                                                minHeight:
                                                    d.jumlah > 0 ? "4px" : "0",
                                            }}
                                            title={`${d.title ?? d.label}: ${d.jumlah} kasus`}
                                        >
                                            {d.jumlah > 0 && (
                                                <span className="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-semibold text-gray-600 whitespace-nowrap">
                                                    {d.jumlah}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <span
                                        className="mt-2 text-[9px] sm:text-[10px] font-medium text-gray-500 text-center leading-tight break-words max-w-[40px] sm:max-w-[50px]"
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
