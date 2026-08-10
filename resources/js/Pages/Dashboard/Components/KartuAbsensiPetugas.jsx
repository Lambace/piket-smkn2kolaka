export default function KartuAbsensiPetugas({ data }) {
    return (
        <div className="rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            <h3 className="mb-4 text-lg font-bold text-white">
                🧑‍ Petugas Piket Hari Ini
            </h3>

            {!data || data.length === 0 ? (
                <p className="text-sm text-slate-400">
                    Belum ada petugas yang absen hari ini.
                </p>
            ) : (
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {data.map((p, i) => (
                        <div
                            key={i}
                            className="flex items-center justify-between rounded-lg bg-slate-700/60 px-4 py-3"
                        >
                            <div>
                                <p className="font-semibold text-white">
                                    {p.nama}
                                </p>
                                <p className="text-xs text-slate-400">
                                    {p.jabatan}
                                </p>
                            </div>
                            <span
                                className={
                                    "rounded-full px-3 py-1 text-xs font-bold " +
                                    (p.status === "tepat_waktu"
                                        ? "bg-green-500/20 text-green-400"
                                        : "bg-amber-500/20 text-amber-400")
                                }
                            >
                                {p.status === "tepat_waktu" ? "✅" : "⏰"}{" "}
                                {p.jam}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
