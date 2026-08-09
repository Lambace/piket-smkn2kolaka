const WARNA = [
    "#6366f1",
    "#ef4444",
    "#f59e0b",
    "#10b981",
    "#8b5cf6",
    "#ec4899",
    "#14b8a6",
    "#f97316",
];

export default function DonutChart({ data = [], judul }) {
    const list = Array.isArray(data) ? data : [];
    const total = list.reduce((s, d) => s + d.jumlah, 0);

    const radius = 56;
    const circumference = 2 * Math.PI * radius;
    let offset = 0;

    return (
        <div className="h-full rounded-xl bg-white p-6 shadow">
            <h3 className="mb-4 text-base font-semibold text-gray-800">
                {judul}
            </h3>
            {total === 0 ? (
                <p className="py-10 text-center text-sm text-gray-400">
                    Belum ada data pada rentang ini
                </p>
            ) : (
                <div className="flex items-center gap-5">
                    <div className="relative h-36 w-36 shrink-0">
                        <svg viewBox="0 0 160 160" className="h-full w-full">
                            {list.map((d, i) => {
                                const frac = d.jumlah / total;
                                const dash = frac * circumference;
                                const el = (
                                    <circle
                                        key={i}
                                        r={radius}
                                        cx={80}
                                        cy={80}
                                        fill="transparent"
                                        stroke={WARNA[i % WARNA.length]}
                                        strokeWidth={26}
                                        strokeDasharray={`${dash} ${circumference - dash}`}
                                        strokeDashoffset={-offset}
                                        transform="rotate(-90 80 80)"
                                    />
                                );
                                offset += dash;
                                return el;
                            })}
                        </svg>
                        <div className="absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-2xl font-extrabold text-gray-800">
                                {total}
                            </span>
                            <span className="text-[10px] text-gray-500">
                                total
                            </span>
                        </div>
                    </div>
                    <div className="min-w-0 flex-1 space-y-2">
                        {list.map((d, i) => (
                            <div
                                key={i}
                                className="flex items-center gap-2 text-xs"
                            >
                                <span
                                    className="h-2.5 w-2.5 shrink-0 rounded-full"
                                    style={{
                                        backgroundColor:
                                            WARNA[i % WARNA.length],
                                    }}
                                ></span>
                                <span className="truncate font-medium text-gray-700">
                                    {d.label}
                                </span>
                                <span className="ml-auto font-semibold text-gray-500">
                                    {d.jumlah} (
                                    {Math.round((d.jumlah / total) * 100)}%)
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
