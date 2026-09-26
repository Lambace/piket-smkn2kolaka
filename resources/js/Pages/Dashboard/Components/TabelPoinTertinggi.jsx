export default function TabelPoinTertinggi({ data }) {
    return (
        <div className="rounded-lg bg-white p-4 sm:p-6 shadow w-full">
            <h3 className="mb-4 text-base font-semibold text-gray-800">
                ⚠️ Poin Pelanggaran Tertinggi
            </h3>
            {data.length === 0 ? (
                <p className="py-8 text-center text-sm text-gray-400">
                    Belum ada data pelanggaran 🎉
                </p>
            ) : (
                // ✅ PERBAIKAN: w-full + overflow-x-auto mencegah card melebar keluar layar di HP
                <div className="w-full overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
                    <table className="w-full text-sm min-w-[500px]">
                        <thead className="border-b border-gray-200 text-left text-xs text-gray-500">
                            <tr>
                                <th className="pb-2 whitespace-nowrap">Nama</th>
                                <th className="pb-2 whitespace-nowrap">
                                    Kelas
                                </th>
                                <th className="pb-2 text-center whitespace-nowrap">
                                    Kasus
                                </th>
                                <th className="pb-2 text-center whitespace-nowrap">
                                    Total Poin
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.map((p, idx) => (
                                <tr
                                    key={idx}
                                    className="border-b border-gray-100 last:border-0"
                                >
                                    <td className="py-2">
                                        <div
                                            className="font-medium truncate max-w-[120px]"
                                            title={p.nama}
                                        >
                                            {p.nama}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {p.nisn}
                                        </div>
                                    </td>
                                    <td className="py-2">
                                        <span className="rounded-md bg-gray-100 px-2 py-0.5 text-xs whitespace-nowrap">
                                            {p.kelas}
                                        </span>
                                    </td>
                                    <td className="py-2 text-center">
                                        <span className="rounded-md bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700 whitespace-nowrap">
                                            {p.jumlah_kasus}x
                                        </span>
                                    </td>
                                    <td className="py-2 text-center">
                                        <span
                                            className={`rounded-md px-2 py-1 text-xs font-bold whitespace-nowrap ${
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
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            <p className="mt-3 text-xs text-gray-400 px-4 sm:px-6">
                💡 Perlu pembinaan dari guru BK
            </p>
        </div>
    );
}
