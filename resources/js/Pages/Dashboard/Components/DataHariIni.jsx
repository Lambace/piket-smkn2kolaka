// ===== 4 Panel data HARI INI untuk TV (Keterlambatan, Izin, Pelanggaran, Tamu) =====

const statusInfo = {
    // Keterlambatan
    tepat_waktu: {
        label: "Tepat Waktu",
        cls: "bg-green-500/20 text-green-300",
        icon: "✅",
    },
    terlambat: {
        label: "Terlambat",
        cls: "bg-amber-500/20 text-amber-300",
        icon: "⏰",
    },
    // Izin Keluar
    menunggu: {
        label: "Menunggu",
        cls: "bg-yellow-500/20 text-yellow-300",
        icon: "⏳",
    },
    disetujui: {
        label: "Disetujui",
        cls: "bg-green-500/20 text-green-300",
        icon: "✓",
    },
    ditolak: { label: "Ditolak", cls: "bg-red-500/20 text-red-300", icon: "✕" },
    kembali: {
        label: "Kembali",
        cls: "bg-blue-500/20 text-blue-300",
        icon: "↩",
    },
    // Pelanggaran
    dicatat: {
        label: "Dicatat",
        cls: "bg-yellow-500/20 text-yellow-300",
        icon: "📝",
    },
    diproses: {
        label: "Diproses",
        cls: "bg-orange-500/20 text-orange-300",
        icon: "⚙",
    },
    selesai: {
        label: "Selesai",
        cls: "bg-green-500/20 text-green-300",
        icon: "✓",
    },
    // Buku Tamu
    di_sekolah: {
        label: "Di Sekolah",
        cls: "bg-emerald-500/20 text-emerald-300",
        icon: "🟢",
    },
    sudah_keluar: {
        label: "Sudah Keluar",
        cls: "bg-slate-500/20 text-slate-300",
        icon: "🚪",
    },
};

const Panel = ({ judul, icon, data, countColor, children }) => (
    <div className="flex flex-col overflow-hidden rounded-2xl bg-slate-800/70 ring-1 ring-white/10 backdrop-blur">
        <div className="flex items-center justify-between border-b border-white/10 px-5 py-3">
            <h3 className="flex items-center gap-2 text-base font-bold text-white">
                <span className="text-xl">{icon}</span>
                {judul}
            </h3>
            <span
                className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${countColor}`}
            >
                {data?.length ?? 0}
            </span>
        </div>
        <div className="max-h-[320px] overflow-y-auto p-3">
            {!data || data.length === 0 ? (
                <p className="py-10 text-center text-sm text-slate-500">
                    Belum ada data hari ini ✨
                </p>
            ) : (
                <div className="space-y-2">{children}</div>
            )}
        </div>
    </div>
);

const Baris = ({ anak, kanan, statusKey, sub }) => {
    const info = statusInfo[statusKey];
    return (
        <div className="flex items-center justify-between gap-3 rounded-lg bg-slate-900/60 px-3 py-2 ring-1 ring-white/5">
            <div className="min-w-0 flex-1">
                <div className="flex items-baseline gap-2">
                    <span className="truncate text-sm font-semibold text-white">
                        {anak}
                    </span>
                    {sub && (
                        <span className="shrink-0 rounded bg-slate-700/80 px-1.5 py-0.5 text-[10px] font-semibold text-slate-300">
                            {sub}
                        </span>
                    )}
                </div>
            </div>
            <div className="flex shrink-0 items-center gap-2">
                {kanan && (
                    <span className="font-mono text-xs text-slate-400">
                        {kanan}
                    </span>
                )}
                {info && (
                    <span
                        className={`rounded px-2 py-0.5 text-[10px] font-bold ${info.cls}`}
                    >
                        {info.icon} {info.label}
                    </span>
                )}
            </div>
        </div>
    );
};

export default function DataHariIni({
    keterlambatanList = [],
    izinKeluarList = [],
    pelanggaranList = [],
    bukuTamuList = [],
}) {
    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            {/* ⏰ Keterlambatan */}
            <Panel
                judul="Keterlambatan"
                icon="⏰"
                data={keterlambatanList}
                countColor="bg-amber-500/20 text-amber-300"
            >
                {keterlambatanList.map((k) => (
                    <Baris
                        key={k.id}
                        anak={k.nama}
                        sub={k.kelas}
                        kanan={`${k.jam_datang?.slice(0, 5) ?? "-"} · ${k.menit_terlambat}m`}
                        statusKey={k.status}
                    />
                ))}
            </Panel>

            {/* 📩 Izin Keluar */}
            <Panel
                judul="Izin Keluar"
                icon="📩"
                data={izinKeluarList}
                countColor="bg-blue-500/20 text-blue-300"
            >
                {izinKeluarList.map((i) => (
                    <Baris
                        key={i.id}
                        anak={i.nama}
                        sub={i.kelas}
                        kanan={i.jam_keluar?.slice(0, 5) ?? "-"}
                        statusKey={i.status}
                    />
                ))}
            </Panel>

            {/* 🔴 Pelanggaran */}
            <Panel
                judul="Pelanggaran"
                icon="🚨"
                data={pelanggaranList}
                countColor="bg-red-500/20 text-red-300"
            >
                {pelanggaranList.map((p) => (
                    <Baris
                        key={p.id}
                        anak={p.jenis_pelanggaran}
                        sub={`${p.kelas} · ${p.poin} pts`}
                        kanan={p.nama}
                        statusKey={p.status}
                    />
                ))}
            </Panel>

            {/* 👥 Buku Tamu */}
            <Panel
                judul="Buku Tamu"
                icon="👥"
                data={bukuTamuList}
                countColor="bg-emerald-500/20 text-emerald-300"
            >
                {bukuTamuList.map((t) => (
                    <Baris
                        key={t.id}
                        anak={t.nama}
                        sub={t.instansi || "Umum"}
                        kanan={t.jam_masuk?.slice(0, 5) ?? "-"}
                        statusKey={t.status}
                    />
                ))}
            </Panel>
        </div>
    );
}
