import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router } from "@inertiajs/react";
import { useState } from "react";

export default function Index({
    ringkasan,
    preview,
    labelPeriode,
    params = {},
}) {
    const today = new Date().toISOString().split("T")[0];

    const [jenis, setJenis] = useState(params.jenis ?? "gabungan");
    const [periode, setPeriode] = useState(params.periode ?? "harian");
    const [tanggal, setTanggal] = useState(params.tanggal ?? today);
    const [semester, setSemester] = useState(params.semester ?? "ganjil");
    const [filterHari, setFilterHari] = useState(
        params.filterHari ?? "Semua Hari",
    );
    const [loading, setLoading] = useState(null);

    // ===== State rentang bebas =====
    const [dari, setDari] = useState(() => {
        const d = new Date();
        d.setMonth(d.getMonth() - 1);
        return d.toISOString().split("T")[0];
    });
    const [sampai, setSampai] = useState(today);

    const apply = () => {
        const query = { jenis, periode, semester, filter_hari: filterHari };
        if (periode === "rentang") {
            query.dari = dari;
            query.sampai = sampai;
        } else {
            query.tanggal = tanggal;
        }

        router.get(route("laporan.index"), query, {
            preserveState: true,
            preserveScroll: true,
        });
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
            semester,
            filter_hari: filterHari,
            ...(periode === "rentang" ? { dari, sampai } : { tanggal }),
        }).toString();

        window.location.href = `${urls[type]}?${query}`;
        setTimeout(() => setLoading(null), 2000);
    };

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm";

    // Helper preset tanggal rentang
    const setPreset = (mode) => {
        const t = new Date();
        if (mode === "30") {
            const ago = new Date();
            ago.setDate(t.getDate() - 30);
            setDari(ago.toISOString().split("T")[0]);
            setSampai(today);
        } else if (mode === "60") {
            const ago = new Date();
            ago.setDate(t.getDate() - 60);
            setDari(ago.toISOString().split("T")[0]);
            setSampai(today);
        } else if (mode === "semester") {
            const start =
                t.getMonth() < 6
                    ? new Date(t.getFullYear(), 0, 1)
                    : new Date(t.getFullYear(), 6, 1);
            setDari(start.toISOString().split("T")[0]);
            setSampai(today);
        } else if (mode === "agustus") {
            setDari("2026-08-01");
            setSampai("2026-10-02");
        }
    };

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
                {/* ===== FORM FILTER ===== */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-base font-semibold text-gray-800">
                        📋 Filter Laporan
                    </h3>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {/* Jenis Laporan */}
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

                        {/* Periode */}
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
                                <option value="rentang">
                                    🔀 Rentang Bebas
                                </option>
                            </select>
                        </div>

                        {/* Filter Hari */}
                        <div>
                            <label className="text-sm font-medium text-gray-700">
                                Filter Hari
                            </label>
                            <select
                                value={filterHari}
                                onChange={(e) => setFilterHari(e.target.value)}
                                className={inputClass}
                            >
                                <option value="Semua Hari">
                                    🌐 Semua Hari
                                </option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        {/* Tanggal acuan (untuk harian/mingguan/bulanan) */}
                        {periode !== "rentang" && (
                            <div>
                                <label className="text-sm font-medium text-gray-700">
                                    {periode === "semester"
                                        ? "Tahun Acuan"
                                        : "Tanggal Acuan"}
                                </label>
                                <input
                                    type="date"
                                    value={tanggal}
                                    onChange={(e) => setTanggal(e.target.value)}
                                    className={inputClass}
                                />
                            </div>
                        )}

                        {/* Semester pilih (hanya untuk semester) */}
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

                    {/* ===== PANEL RENTANG BEBAS ===== */}
                    {periode === "rentang" && (
                        <div className="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                            <div className="mb-3 flex items-center gap-2">
                                <span className="text-lg">🔀</span>
                                <h4 className="text-sm font-bold text-indigo-900">
                                    Rentang Tanggal Bebas
                                </h4>
                            </div>

                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div>
                                    <label className="mb-1 block text-xs font-semibold text-gray-700">
                                        📅 Dari Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={dari}
                                        onChange={(e) =>
                                            setDari(e.target.value)
                                        }
                                        className={inputClass}
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-xs font-semibold text-gray-700">
                                        📅 Sampai Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={sampai}
                                        onChange={(e) =>
                                            setSampai(e.target.value)
                                        }
                                        className={inputClass}
                                    />
                                </div>
                            </div>

                            <div className="mt-3 flex flex-wrap gap-1">
                                <button
                                    type="button"
                                    onClick={() => setPreset("30")}
                                    className="rounded bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200"
                                >
                                    30 hari terakhir
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPreset("60")}
                                    className="rounded bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200"
                                >
                                    60 hari terakhir
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPreset("semester")}
                                    className="rounded bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200"
                                >
                                    Semester berjalan
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPreset("agustus")}
                                    className="rounded bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
                                >
                                    📊 1 Ags – 2 Okt
                                </button>
                            </div>
                        </div>
                    )}

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

                        <button
                            onClick={() => exportFile("daftar-hadir")}
                            disabled={loading === "daftar-hadir"}
                            className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            title="Daftar hadir piket format resmi kedinasan (H/A/I/S/DL)"
                        >
                            {loading === "daftar-hadir"
                                ? "⏳ Menyiapkan..."
                                : "📋 Download Daftar Hadir Piket"}
                        </button>
                    </div>
                </div>

                {/* ===== RINGKASAN ===== */}
                <div className="rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 p-6 text-white shadow">
                    <h3 className="mb-3 text-base font-semibold">
                        📊 Ringkasan Data
                    </h3>
                    <p className="text-xs opacity-80">
                        Periode: {labelPeriode}
                    </p>
                    <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-5">
                        {[
                            {
                                label: "Total Record",
                                value: ringkasan?.total ?? 0,
                            },
                            {
                                label: "Terlambat",
                                value: ringkasan?.keterlambatan ?? 0,
                            },
                            {
                                label: "Izin Keluar",
                                value: ringkasan?.izin_keluar ?? 0,
                            },
                            {
                                label: "Pelanggaran",
                                value: ringkasan?.pelanggaran ?? 0,
                            },
                            { label: "Tamu", value: ringkasan?.tamu ?? 0 },
                        ].map((item, i) => (
                            <div
                                key={i}
                                className="rounded-lg bg-white/15 p-3 backdrop-blur"
                            >
                                <p className="text-xs opacity-80">
                                    {item.label}
                                </p>
                                <p className="text-2xl font-bold">
                                    {item.value}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ===== PREVIEW TABEL ===== */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 text-base font-semibold text-gray-800">
                        👁️ Preview Data (15 teratas)
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
