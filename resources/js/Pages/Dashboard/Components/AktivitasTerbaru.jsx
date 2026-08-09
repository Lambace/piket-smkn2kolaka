const warnaDot = {
    red: "bg-red-500",
    yellow: "bg-yellow-500",
    orange: "bg-orange-500",
    blue: "bg-blue-500",
};

const waktuRelatif = (iso) => {
    if (!iso) return "-";
    const d = new Date(iso);
    const diffMin = Math.floor((Date.now() - d.getTime()) / 60000);
    if (diffMin < 1) return "Baru saja";
    if (diffMin < 60) return `${diffMin} mnt lalu`;
    const h = Math.floor(diffMin / 60);
    if (h < 24) return `${h} jam lalu`;
    return d.toLocaleDateString("id-ID", { day: "2-digit", month: "short" });
};

export default function AktivitasTerbaru({ data = [] }) {
    const list = Array.isArray(data) ? data : [];

    return (
        <div className="h-full rounded-xl bg-white p-6 shadow">
            <h3 className="mb-4 text-base font-semibold text-gray-800">
                🕒 Aktivitas Terbaru
            </h3>
            {list.length === 0 ? (
                <p className="py-10 text-center text-sm text-gray-400">
                    Belum ada aktivitas
                </p>
            ) : (
                <ol className="relative space-y-4 border-l-2 border-gray-100 pl-5">
                    {list.map((a, i) => (
                        <li key={i} className="relative">
                            <span
                                className={`absolute -left-[27px] top-1 h-3 w-3 rounded-full ring-4 ring-white ${warnaDot[a.warna] ?? "bg-gray-400"}`}
                            ></span>
                            <div className="flex items-center gap-2">
                                <span className="rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600">
                                    {a.tipe}
                                </span>
                                <span className="text-[10px] text-gray-400">
                                    {waktuRelatif(a.waktu)}
                                </span>
                            </div>
                            <p className="mt-0.5 text-xs leading-relaxed text-gray-700">
                                {a.teks}
                            </p>
                        </li>
                    ))}
                </ol>
            )}
        </div>
    );
}
