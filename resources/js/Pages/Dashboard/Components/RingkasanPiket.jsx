export default function RingkasanPiket({ chartData, chartPelanggaran }) {
    const dataKelas = Array.isArray(chartData) ? chartData : [];
    const dataHari = Array.isArray(chartPelanggaran) ? chartPelanggaran : [];

    const totalTerlambat = dataKelas.reduce((s, d) => s + d.jumlah, 0);
    const totalPelanggaran = dataHari.reduce((s, d) => s + d.jumlah, 0);
    const kelasTertinggi = dataKelas.reduce(
        (m, d) => (d.jumlah > (m?.jumlah ?? -1) ? d : m),
        null,
    );
    const hariPelanggaran = dataHari.reduce(
        (m, d) => (d.jumlah > (m?.jumlah ?? -1) ? d : m),
        null,
    );
    const jumlahHari = dataHari.length || 1;

    return (
        <div className="h-full rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 p-6 text-white shadow">
            <h3 className="mb-4 text-base font-semibold">📊 Ringkasan Piket</h3>
            <div className="space-y-4">
                <div>
                    <p className="text-xs opacity-80">Total keterlambatan</p>
                    <p className="text-2xl font-bold">{totalTerlambat} siswa</p>
                    <p className="text-[10px] opacity-70">
                        Rata-rata {Math.round(totalTerlambat / jumlahHari)}/hari
                    </p>
                </div>
                <div>
                    <p className="text-xs opacity-80">Total pelanggaran</p>
                    <p className="text-2xl font-bold">
                        {totalPelanggaran} kasus
                    </p>
                </div>
                <div className="border-t border-white/20 pt-3">
                    <p className="text-xs opacity-80">
                        Kelas keterlambatan tertinggi
                    </p>
                    <p className="text-sm font-semibold">
                        {kelasTertinggi
                            ? `${kelasTertinggi.label} (${kelasTertinggi.jumlah})`
                            : "-"}
                    </p>
                </div>
                <div>
                    <p className="text-xs opacity-80">
                        Hari pelanggaran tertinggi
                    </p>
                    <p className="text-sm font-semibold">
                        {hariPelanggaran
                            ? `${hariPelanggaran.title ?? hariPelanggaran.label} (${hariPelanggaran.jumlah})`
                            : "-"}
                    </p>
                </div>
            </div>
        </div>
    );
}
