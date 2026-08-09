export default function TabelTerlambatTertinggi({ data }) {
    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-4 text-base font-semibold text-gray-800">
                ⏰ Siswa Paling Sering Terlambat
            </h3>
            {data.length === 0 ? (
                <p className="py-8 text-center text-sm text-gray-400">
                    Belum ada keterlambatan 🎉
                </p>
            ) : (
                <table className="w-full text-sm">
                    <thead className="border-b border-gray-200 text-left text-xs text-gray-500">
                        <tr>
                            <th className="pb-2">Nama</th>
                            <th className="pb-2">Kelas</th>
                            <th className="pb-2 text-center">Jumlah</th>
                            <th className="pb-2 text-center">Rata²</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.map((k, idx) => (
                            <tr
                                key={idx}
                                className="border-b border-gray-100 last:border-0"
                            >
                                <td className="py-2">
                                    <div className="font-medium">{k.nama}</div>
                                    <div className="text-xs text-gray-500">
                                        {k.nisn}
                                    </div>
                                </td>
                                <td className="py-2">
                                    <span className="rounded-md bg-gray-100 px-2 py-0.5 text-xs">
                                        {k.kelas}
                                    </span>
                                </td>
                                <td className="py-2 text-center">
                                    <span
                                        className={`rounded-md px-2 py-1 text-xs font-bold ${
                                            k.jumlah >= 5
                                                ? "bg-red-100 text-red-700"
                                                : k.jumlah >= 3
                                                  ? "bg-orange-100 text-orange-700"
                                                  : "bg-blue-100 text-blue-700"
                                        }`}
                                    >
                                        {k.jumlah}x
                                    </span>
                                </td>
                                <td className="py-2 text-center text-xs text-gray-500">
                                    {k.rata_menit} mnt
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
            <p className="mt-3 text-xs text-gray-400">
                💡 Perlu pembinaan dari wali kelas
            </p>
        </div>
    );
}
