export default function KartuAbsensiPetugas({ data }) {
    const getBadge = (status, jam) => {
        switch (status) {
            case "tepat_waktu":
                return {
                    className:
                        "bg-green-500/20 text-green-400 border border-green-500/30",
                    icon: "✅",
                    text: jam,
                };
            case "terlambat":
                return {
                    className:
                        "bg-amber-500/20 text-amber-400 border border-amber-500/30",
                    icon: "⏰",
                    text: jam,
                };
            case "alpha":
                return {
                    className:
                        "bg-red-500/20 text-red-400 border border-red-500/30 animate-pulse",
                    icon: "❌",
                    text: "ALPHA",
                };
            default:
                return { className: "", icon: "", text: "" };
        }
    };

    const countByStatus = (status) =>
        data?.filter((p) => p.status === status).length ?? 0;

    const totalHadir =
        countByStatus("tepat_waktu") + countByStatus("terlambat");
    const totalAlpha = countByStatus("alpha");

    return (
        <div className="rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            {/* Header dengan ringkasan */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h3 className="text-lg font-bold text-white">
                    🧑‍🏫 Petugas Piket Hari Ini
                </h3>
                {data && data.length > 0 && (
                    <div className="flex gap-2 text-xs">
                        <span className="rounded-full bg-green-500/20 px-2.5 py-1 font-semibold text-green-400">
                            ✅ Hadir {totalHadir}
                        </span>
                        {totalAlpha > 0 && (
                            <span className="rounded-full bg-red-500/20 px-2.5 py-1 font-semibold text-red-400">
                                ❌ Alpha {totalAlpha}
                            </span>
                        )}
                    </div>
                )}
            </div>

            {!data || data.length === 0 ? (
                <p className="text-sm text-slate-400">
                    Belum ada petugas yang absen hari ini.
                </p>
            ) : (
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {data.map((p, i) => {
                        const badge = getBadge(p.status, p.jam);
                        return (
                            <div
                                key={i}
                                className={`flex items-center justify-between rounded-lg px-4 py-3 ${
                                    p.status === "alpha"
                                        ? "bg-red-900/30 border border-red-500/30"
                                        : "bg-slate-700/60"
                                }`}
                            >
                                <div className="min-w-0">
                                    <p className="truncate font-semibold text-white">
                                        {p.nama}
                                    </p>
                                    <p className="truncate text-xs text-slate-400">
                                        {p.jabatan}
                                    </p>
                                </div>
                                <span
                                    className={`ml-2 shrink-0 rounded-full px-3 py-1 text-xs font-bold ${badge.className}`}
                                >
                                    {badge.icon} {badge.text}
                                </span>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
