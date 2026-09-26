export default function DistribusiPelanggaran({ data }) {
    const total = (data ?? []).reduce((a, d) => a + d.jumlah, 0);

    const warna = [
        "bg-red-500",
        "bg-orange-500",
        "bg-amber-400",
        "bg-yellow-400",
        "bg-gray-400",
    ];

    return (
        <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h4 className="mb-1 font-bold text-gray-800">
                🎯 Distribusi Jenis Pelanggaran
            </h4>
            <p className="mb-4 text-[10px] text-gray-400">
                Total {total} kasus pada rentang terpilih
            </p>

            {!data || data.length === 0 ? (
                <p className="py-6 text-center text-sm text-gray-400">
                    Belum ada data pelanggaran. 🎉
                </p>
            ) : (
                <div className="space-y-4">
                    {data.map((d, i) => {
                        const persen =
                            total > 0
                                ? Math.round((d.jumlah / total) * 100)
                                : 0;

                        return (
                            <div key={d.label ?? i}>
                                {/* Baris label: nama jenis + jumlah & persen */}
                                <div className="mb-1 flex items-center justify-between gap-2">
                                    <span className="truncate text-sm font-semibold text-gray-700">
                                        {d.label ?? "Tanpa jenis"}
                                    </span>
                                    <span className="shrink-0 text-xs font-bold text-gray-600">
                                        {d.jumlah} kasus ({persen}%)
                                    </span>
                                </div>

                                {/* Bar berwarna sesuai peringkat */}
                                <div className="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div
                                        className={`h-full rounded-full ${warna[i] ?? "bg-gray-400"}`}
                                        style={{ width: `${persen}%` }}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
