export default function KelasMelanggarTertinggi({ data }) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h4 className="mb-3 font-bold text-gray-800">
                🏆 Kelas Melanggar Tertinggi
            </h4>

            {!data || data.length === 0 ? (
                <p className="py-6 text-center text-sm text-gray-400">
                    Belum ada data pelanggaran. 🎉
                </p>
            ) : (
                <div className="space-y-2">
                    {data.map((k, i) => (
                        <div
                            key={k.kelas}
                            className="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2"
                        >
                            <div className="flex items-center gap-2">
                                <span
                                    className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-extrabold text-white ${
                                        i === 0
                                            ? "bg-red-500"
                                            : i === 1
                                              ? "bg-orange-400"
                                              : i === 2
                                                ? "bg-yellow-400"
                                                : "bg-gray-400"
                                    }`}
                                >
                                    {i + 1}
                                </span>
                                <span className="text-sm font-semibold text-gray-800">
                                    {k.kelas}
                                </span>
                            </div>
                            <div className="text-right">
                                <p className="text-sm font-bold text-red-600">
                                    {k.jumlah} kasus
                                </p>
                                <p className="text-[10px] text-gray-500">
                                    {k.total_poin} poin
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
