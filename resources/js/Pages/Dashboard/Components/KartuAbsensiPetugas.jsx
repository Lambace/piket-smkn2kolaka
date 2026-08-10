export default function KartuAbsensiPetugas({ data }) {
    const hadirList = data?.filter((p) => p.status !== "alpha") ?? [];
    const alphaList = data?.filter((p) => p.status === "alpha") ?? [];

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
            default:
                return { className: "", icon: "", text: "" };
        }
    };

    return (
        <div className="rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            <h3 className="mb-4 text-lg font-bold text-white">
                🧑‍🏫 Petugas Piket Hari Ini
            </h3>

            {!data || data.length === 0 ? (
                <p className="text-sm text-slate-400">
                    Belum ada petugas yang absen hari ini.
                </p>
            ) : (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {/* ===== KOLOM KIRI: HADIR ===== */}
                    <div className="rounded-lg border border-slate-700 bg-slate-900/50 p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h4 className="flex items-center gap-2 text-sm font-bold text-green-400">
                                <span>✅</span>
                                <span>Hadir</span>
                            </h4>
                            <span className="rounded-full bg-green-500/20 px-2.5 py-0.5 text-xs font-bold text-green-400">
                                {hadirList.length} orang
                            </span>
                        </div>

                        {hadirList.length === 0 ? (
                            <p className="py-4 text-center text-sm text-slate-500">
                                Belum ada yang absen
                            </p>
                        ) : (
                            <div className="space-y-2">
                                {hadirList.map((p, i) => {
                                    const badge = getBadge(p.status, p.jam);
                                    return (
                                        <div
                                            key={i}
                                            className="flex items-center justify-between rounded-lg bg-slate-700/60 px-3 py-2.5"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold text-white">
                                                    {p.nama}
                                                </p>
                                                <p className="truncate text-xs text-slate-400">
                                                    {p.jabatan}
                                                </p>
                                            </div>
                                            <span
                                                className={`ml-2 shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${badge.className}`}
                                            >
                                                {badge.icon} {badge.text}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* ===== KOLOM KANAN: ALPHA ===== */}
                    <div className="rounded-lg border border-red-900/50 bg-red-950/30 p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h4 className="flex items-center gap-2 text-sm font-bold text-red-400">
                                <span>❌</span>
                                <span>Alpha</span>
                            </h4>
                            <span className="rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-bold text-red-400">
                                {alphaList.length} orang
                            </span>
                        </div>

                        {alphaList.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-6 text-center">
                                <span className="mb-2 text-3xl">🎉</span>
                                <p className="text-sm font-semibold text-green-400">
                                    Semua petugas hadir!
                                </p>
                                <p className="mt-0.5 text-xs text-slate-500">
                                    Tidak ada yang alpha hari ini
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {alphaList.map((p, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between rounded-lg bg-red-900/40 px-3 py-2.5"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold text-white">
                                                {p.nama}
                                            </p>
                                            <p className="truncate text-xs text-slate-400">
                                                {p.jabatan}
                                            </p>
                                        </div>
                                        <span className="ml-2 shrink-0 animate-pulse rounded-full border border-red-500/30 bg-red-500/20 px-2.5 py-1 text-xs font-bold text-red-400">
                                            ❌ ALPHA
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
