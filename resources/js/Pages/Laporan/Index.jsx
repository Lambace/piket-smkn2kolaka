import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const today = new Date().toISOString().split("T")[0];

export default function Index({
    ringkasan,
    preview,
    labelPeriode,
    params = {},
}) {
    const [jenis, setJenis] = useState(params.jenis ?? "gabungan");
    const [periode, setPeriode] = useState(params.periode ?? "harian");
    const [tanggal, setTanggal] = useState(params.tanggal ?? today);
    const [semester, setSemester] = useState(params.semester ?? "ganjil");
    const [loading, setLoading] = useState(null);

    const apply = () => {
        router.get(
            route("laporan.index"),
            { jenis, periode, tanggal, semester },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const exportFile = (type) => {
        setLoading(type);

        const urls = {
            pdf: route("laporan.pdf"),
            "daftar-hadir": route("laporan.daftar-hadir"),
        };

        const query = new URLSearchParams({
            jenis,
            periode,
            tanggal,
            semester,
        }).toString();

        window.location.href = `${urls[type]}?${query}`;
        setTimeout(() => setLoading(null), 2000);
    };

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm";

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Laporan Piket
                </h2>
            }
        >
            <Head title="Laporan" />

            <div className="space-y-6">
                {/* Form Filter */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-base font-semibold text-gray-800">
                        📋 Filter Laporan
                    </h3>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label className="text-sm font-medium text-gray-700">
                                Jenis Laporan
                            </label>
                            <select
                                value={jenis}
                                onChange={(e) => setJenis(e.target.value)}
                                className={inputClass}
                            >
                                <option value="gabungan">
                                    📊 Gabungan (Semua)
                                </option>
                                <option value="keterlambatan">
                                    ⏰ Keterlambatan
                                </option>
                                <option value="izin_keluar">
                                    🚪 Izin Keluar
                                </option>
                                <option value="pelanggaran">
                                    ⚠️ Pelanggaran
                                </option>
                                <option value="tamu">👤 Buku Tamu</option>
                            </select>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-700">
                                Periode
                            </label>
                            <select
                                value={periode}
                                onChange={(e) => setPeriode(e.target.value)}
                                className={inputClass}
                            >
                                <option value="harian">📅 Harian</option>
                                <option value="mingguan">🗓️ Mingguan</option>
                                <option value="bulanan">📆 Bulanan</option>
                                <option value="semester">🎓 Semester</option>
                            </select>
                        </div>

                        <div>
                            <label className="text-sm font-medium text-gray-700">
                                {periode === "semester" ? "Tahun" : "Tanggal"}
                            </label>
                            <input
                                type="date"
                                value={tanggal}
                                onChange={(e) => setTanggal(e.target.value)}
                                className={inputClass}
                            />
                        </div>

                        {periode === "semester" && (
                            <div>
                                <label className="text-sm font-medium text-gray-700">
                                    Semester
                                </label>
                                <select
                                    value={semester}
                                    onChange={(e) =>
                                        setSemester(e.target.value)
                                    }
                                    className={inputClass}
                                >
                                    <option value="ganjil">
                                        Ganjil (Jul - Des)
                                    </option>
                                    <option value="genap">
                                        Genap (Jan - Jun)
                                    </option>
                                </select>
                            </div>
                        )}
                    </div>

                    {/* ===== TOMBOL AKSI ===== */}
                    <div className="mt-5 flex flex-wrap gap-2">
                        <button
                            onClick={apply}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            🔍 Tampilkan Data
                        </button>

                        <button
                            onClick={() => exportFile("pdf")}
                            disabled={loading === "pdf"}
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            {loading === "pdf"
                                ? "⏳ Menyiapkan..."
                                : "📄 Download Laporan"}
                        </button>

                        {/* ===== BARU: Daftar Hadir Piket ===== */}
                        <button
                            onClick={() => exportFile("daftar-hadir")}
                            disabled={loading === "daftar-hadir"}
                            className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            title="Download daftar hadir piket format resmi kedinasan (H/A/I/S/DL)"
                        >
                            {loading === "daftar-hadir"
                                ? "⏳ Menyiapkan..."
                                : "📋 Download Daftar Hadir Piket"}
                        </button>
                    </div>
                </div>

                {/* Ringkasan */}
                <div className="rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 p-6 text-white shadow">
                    <h3 className="mb-3 text-base font-semibold">
                        📊 Ringkasan Data
                    </h3>
                    <p className="text-xs opacity-80">
                        Periode: {labelPeriode}
                    </p>
                    <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-5">
                        <div className="rounded-lg bg-white/15 p-3 backdrop-blur">
                            <p className="text-xs opacity-80">Total Record</p>
                            <p className="text-2xl font-bold">
                                {ringkasan?.total ?? 0}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white/15 p-3 backdrop-blur">
                            <p className="text-xs opacity-80">Terlambat</p>
                            <p className="text-2xl font-bold">
                                {ringkasan?.keterlambatan ?? 0}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white/15 p-3 backdrop-blur">
                            <p className="text-xs opacity-80">Izin Keluar</p>
                            <p className="text-2xl font-bold">
                                {ringkasan?.izin_keluar ?? 0}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white/15 p-3 backdrop-blur">
                            <p className="text-xs opacity-80">Pelanggaran</p>
                            <p className="text-2xl font-bold">
                                {ringkasan?.pelanggaran ?? 0}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white/15 p-3 backdrop-blur">
                            <p className="text-xs opacity-80">Tamu</p>
                            <p className="text-2xl font-bold">
                                {ringkasan?.tamu ?? 0}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Preview Tabel */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-base font-semibold text-gray-800">
                        👁️ Preview Data (10 teratas)
                    </h3>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 text-left text-xs text-gray-600">
                                <tr>
                                    <th className="p-3">Jenis</th>
                                    <th className="p-3">Tanggal</th>
                                    <th className="p-3">Nama</th>
                                    <th className="p-3">Kelas</th>
                                    <th className="p-3">Detail</th>
                                    <th className="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {!preview || preview.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan="6"
                                            className="p-8 text-center text-gray-400"
                                        >
                                            Tidak ada data pada periode ini.
                                        </td>
                                    </tr>
                                ) : (
                                    preview.map((row, i) => (
                                        <tr
                                            key={i}
                                            className="border-t border-gray-200"
                                        >
                                            <td className="p-3">
                                                <span
                                                    className={`rounded-md px-2 py-1 text-xs font-semibold ${
                                                        row.jenis_aktivitas ===
                                                        "Keterlambatan"
                                                            ? "bg-red-100 text-red-700"
                                                            : row.jenis_aktivitas ===
                                                                "Izin Keluar"
                                                              ? "bg-yellow-100 text-yellow-700"
                                                              : row.jenis_aktivitas ===
                                                                  "Pelanggaran"
                                                                ? "bg-orange-100 text-orange-700"
                                                                : "bg-blue-100 text-blue-700"
                                                    }`}
                                                >
                                                    {row.jenis_aktivitas}
                                                </span>
                                            </td>
                                            <td className="p-3 text-xs">
                                                {row.tanggal}
                                            </td>
                                            <td className="p-3 font-medium">
                                                {row.siswa}
                                            </td>
                                            <td className="p-3 text-xs">
                                                {row.kelas}
                                            </td>
                                            <td className="p-3 text-xs">
                                                {row.detail}
                                            </td>
                                            <td className="p-3 text-xs">
                                                {row.status}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
