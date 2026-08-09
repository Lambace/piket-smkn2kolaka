export default function TerlambatPerKelas({ data }) {
    const max = Math.max(...data.map((d) => d.jumlah), 1);
    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-4 text-base font-semibold text-gray-800">
                🏫 Keterlambatan per Kelas
            </h3>
            {data.length === 0 ? (
                <p className="py-8 text-center text-sm text-gray-400">
                    Belum ada data
                </p>
            ) : (
                <div className="space-y-2">
                    {data.map((d, idx) => (
                        <div key={idx} className="flex items-center gap-3">
                            <span className="w-20 text-xs font-medium text-gray-600">
                                {d.kelas}
                            </span>
                            <div className="flex-1 h-6 rounded bg-gray-100 overflow-hidden">
                                <div
                                    className="h-full bg-gradient-to-r from-red-400 to-red-600 flex items-center justify-end px-2"
                                    style={{
                                        width: `${(d.jumlah / max) * 100}%`,
                                    }}
                                >
                                    <span className="text-[10px] font-bold text-white">
                                        {d.jumlah}
                                    </span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
