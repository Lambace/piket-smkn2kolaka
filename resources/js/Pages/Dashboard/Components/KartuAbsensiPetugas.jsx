import { usePage } from "@inertiajs/react";

export default function KartuAbsensiPetugas({ data }) {
    // displayKey hanya ada di halaman TV → penentu route publik/auth
    const displayKey = usePage().props.displayKey ?? null;

    const hadirList = data?.filter((p) => p.status !== "alpha") ?? [];
    const alphaList = data?.filter((p) => p.status === "alpha") ?? [];

    // ===== Download Daftar Hadir (otomatis pilih route) =====
    const downloadDaftarHadir = () => {
        const today = new Date().toISOString().split("T")[0];
        const params = new URLSearchParams({
            periode: "harian",
            tanggal: today,
        });
        if (displayKey) params.set("k", displayKey);

        const url = displayKey
            ? route("tampil.daftar-hadir") // dari TV (publik + key)
            : route("laporan.daftar-hadir"); // dari Dashboard (login)

        window.location.href = `${url}?${params.toString()}`;
    };

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
            {/* ===== HEADER + TOMBOL (SELALU MUNCUL) ===== */}
            <div className="mb-4 flex items-center justify-between border-b border-slate-700 pb-3">
                <h3 className="text-lg font-bold text-white">
                    🧑‍🏫 Petugas Piket Hari Ini
                </h3>
                <button
                    onClick={downloadDaftarHadir}
                    className="flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow transition hover:bg-blue-700"
                    title="Download daftar hadir format resmi kedinasan (H/A/I/S/DL)"
                >
                    📋 Daftar Hadir
                </button>
            </div>

            {/* ===== BELUM ADA DATA SAMA SEKALI ===== */}
            {!data || data.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-8 text-center">
                    <span className="mb-2 text-4xl">📭</span>
                    <p className="text-sm font-semibold text-slate-300">
                        Belum ada data absensi hari ini
                    </p>
                    <p className="mt-1 text-xs text-slate-500">
                        Kartu akan terisi otomatis setelah petugas melakukan
                        absen masuk.
                    </p>
                </div>
            ) : (
                <div className="space-y-4">
                    {/* ===== PERINGATAN: 0 HADIR + ADA ALPHA ===== */}
                    {hadirList.length === 0 && alphaList.length > 0 && (
                        <div className="flex items-center gap-3 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3">
                            <span className="text-xl">⚠️</span>
                            <p className="text-sm text-amber-300">
                                <strong>Belum ada petugas yang hadir.</strong>{" "}
                                {alphaList.length} petugas tercatat tidak hadir
                                (alpha).
                            </p>
                        </div>
                    )}

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
                                <div className="flex flex-col items-center justify-center py-6 text-center">
                                    <span className="mb-2 text-3xl">🕐</span>
                                    <p className="text-sm font-semibold text-slate-400">
                                        Belum ada petugas yang hadir
                                    </p>
                                    <p className="mt-0.5 text-xs text-slate-500">
                                        Menunggu absensi masuk hari ini…
                                    </p>
                                </div>
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
                                    <span>Tidak Hadir (Alpha)</span>
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
                </div>
            )}
        </div>
    );
}
