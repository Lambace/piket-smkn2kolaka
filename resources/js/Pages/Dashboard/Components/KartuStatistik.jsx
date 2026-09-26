export default function KartuStatistik({ stats }) {
    const kartu = [
        {
            label: "Total Siswa Aktif",
            value: stats.total_siswa,
            icon: "👥",
            bg: "bg-blue-500",
            desc: "Siswa terdaftar",
        },
        {
            label: "Terlambat",
            value: stats.terlambat,
            icon: "⏰",
            bg: stats.terlambat > 0 ? "bg-red-500" : "bg-gray-400",
            desc: "Dalam rentang filter",
        },
        {
            label: "Izin Keluar",
            value: stats.izin_keluar,
            icon: "🚪",
            bg: "bg-yellow-500",
            desc: "Dalam rentang filter",
        },
        {
            label: "Pelanggaran",
            value: stats.pelanggaran,
            icon: "⚠️",
            bg: "bg-orange-500",
            desc: "Dalam rentang filter",
        },
        {
            label: "Tamu",
            value: stats.tamu,
            icon: "👤",
            bg: "bg-indigo-500",
            desc: `${stats.tamu_masih_di_sekolah} masih di sekolah`,
        },
    ];

    return (
        <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
            {kartu.map((k) => (
                <div
                    key={k.label}
                    className="rounded-lg bg-white p-5 shadow transition hover:shadow-md"
                >
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-xs font-medium text-gray-500">
                                {k.label}
                            </p>
                            <p className="mt-2 text-3xl font-bold text-gray-800">
                                {k.value}
                            </p>
                            <p className="mt-1 text-xs text-gray-400">
                                {k.desc}
                            </p>
                        </div>
                        <div
                            className={`flex h-10 w-10 items-center justify-center rounded-lg ${k.bg} text-xl`}
                        >
                            {k.icon}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
