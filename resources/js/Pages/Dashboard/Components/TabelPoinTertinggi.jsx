export default function TabelPoinTertinggi({ data }) {
    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-4 text-base font-semibold text-gray-800">
                ⚠️ Siswa Poin Tertinggi
            </h3>
            {data.length === 0 ? (
                <p className="py-8 text-center text-sm text-gray-400">
                    Belum ada pelanggaran 🎉
                </p>
            ) : (
                <table className="w-full text-sm">
                    <thead className="border-b border-gray-200 text-left text-xs text-gray-500">
                        <tr>
                            <th className="pb-2">Nama</th>
                            <th className="pb-2">Kelas</th>
                            <th className="pb-2 text-center">Poin</th>
                            <th className="pb-2 text-center">Kasus</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.map((p, idx) => (
                            <tr
                                key={idx}
                                className="border-b border-gray-100 last:border-0"
                            >
                                <td className="py-2">
                                    <div className="font-medium">{p.nama}</div>
                                    <div className="text-xs text-gray-500">
                                        {p.nisn}
                                    </div>
                                </td>
                                <td className="py-2">
                                    <span className="rounded-md bg-gray-100 px-2 py-0.5 text-xs">
                                        {p.kelas}
                                    </span>
                                </td>
                                <td className="py-2 text-center">
                                    <span
                                        className={`rounded-md px-2 py-1 text-xs font-bold ${
                                            p.total_poin >= 50
                                                ? "bg-red-100 text-red-700"
                                                : p.total_poin >= 20
                                                  ? "bg-orange-100 text-orange-700"
                                                  : "bg-yellow-100 text-yellow-700"
                                        }`}
                                    >
                                        {p.total_poin}
                                    </span>
                                </td>
                                <td className="py-2 text-center text-xs text-gray-500">
                                    {p.jumlah_kasus}x
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
            <p className="mt-3 text-xs text-gray-400">
                💡 Untuk tindak lanjut oleh BK
            </p>
        </div>
    );
}
