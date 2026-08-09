export default function DistribusiPelanggaran({ data }) {
    const total = data.reduce((s, d) => s + d.jumlah, 0);
    const colors = [
        "bg-red-500",
        "bg-orange-500",
        "bg-yellow-500",
        "bg-blue-500",
        "bg-purple-500",
    ];

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-4 text-base font-semibold text-gray-800">
                🎯 Distribusi Jenis Pelanggaran
            </h3>
            {data.length === 0 ? (
                <p className="py-8 text-center text-sm text-gray-400">
                    Belum ada pelanggaran
                </p>
            ) : (
                <div className="space-y-3">
                    {data.map((d, idx) => {
                        const percent =
                            total > 0 ? (d.jumlah / total) * 100 : 0;
                        return (
                            <div key={idx}>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="font-medium text-gray-700">
                                        {d.jenis_pelanggaran}
                                    </span>
                                    <span className="text-xs text-gray-500">
                                        {d.jumlah} ({percent.toFixed(1)}%)
                                    </span>
                                </div>
                                <div className="mt-1 h-2 w-full rounded-full bg-gray-100">
                                    <div
                                        className={`h-2 rounded-full ${colors[idx % colors.length]}`}
                                        style={{ width: `${percent}%` }}
                                    ></div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
