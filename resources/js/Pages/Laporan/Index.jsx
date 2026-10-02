import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router } from "@inertiajs/react";
import { useState } from "react";

export default function Index({
    ringkasan,
    preview,
    labelPeriode,
    params = {},
}) {
    const today = new Date();
    const todayStr = today.toISOString().split("T")[0];

    // ===== NORMALISASI: tangani `?periode=rentang` dari URL =====
    const isRentang =
        params.periode === "rentang" || params.periode === "rentang_bulanan";

    const [jenis, setJenis] = useState(params.jenis ?? "gabungan");
    const [periode, setPeriode] = useState(
        isRentang ? "rentang_bulanan" : (params.periode ?? "harian"),
    );
    const [tanggal, setTanggal] = useState(params.tanggal ?? todayStr);
    const [semester, setSemester] = useState(params.semester ?? "ganjil");
    const [filterHari, setFilterHari] = useState(
        params.filterHari ?? "Semua Hari",
    );
    const [loading, setLoading] = useState(null);

    const [dari, setDari] = useState(params.dari ?? "");
    const [sampai, setSampai] = useState(params.sampai ?? "");
    const [rentangBulan, setRentangBulan] = useState("custom");

    const getPreset = (key) => {
        const now = new Date();
        const fmt = (d) => d.toISOString().split("T")[0];
        switch (key) {
            case "bulan_ini": {
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                return { dari: fmt(start), sampai: fmt(now) };
            }
            case "bulan_lalu": {
                const start = new Date(
                    now.getFullYear(),
                    now.getMonth() - 1,
                    1,
                );
                const end = new Date(now.getFullYear(), now.getMonth(), 0);
                return { dari: fmt(start), sampai: fmt(end) };
            }
            case "2_bulan": {
                const ago = new Date();
                ago.setDate(now.getDate() - 60);
                return { dari: fmt(ago), sampai: fmt(now) };
            }
            case "3_bulan": {
                const ago = new Date();
                ago.setDate(now.getDate() - 90);
                return { dari: fmt(ago), sampai: fmt(now) };
            }
            case "semester": {
                const start =
                    now.getMonth() < 6
                        ? new Date(now.getFullYear(), 0, 1)
                        : new Date(now.getFullYear(), 6, 1);
                return { dari: fmt(start), sampai: fmt(now) };
            }
            case "1ags_2okt":
                return { dari: "2026-08-01", sampai: "2026-10-02" };
            default:
                return { dari, sampai };
        }
    };

    const handleRentangChange = (key) => {
        setRentangBulan(key);
        if (key && key !== "custom") {
            const preset = getPreset(key);
            setDari(preset.dari);
            setSampai(preset.sampai);
        }
    };

    const handlePeriodeChange = (value) => {
        setPeriode(value);
        if (value === "rentang_bulanan" && !dari) {
            handleRentangChange("bulan_ini");
        }
    };

    const apply = () => {
        const query = { jenis, semester, filter_hari: filterHari };
        if (["rentang", "rentang_bulanan"].includes(periode)) {
            query.periode = "rentang";
            query.dari = dari;
            query.sampai = sampai;
        } else {
            query.periode = periode;
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
        const queryParams = { jenis, semester, filter_hari: filterHari };
        if (["rentang", "rentang_bulanan"].includes(periode)) {
            queryParams.periode = "rentang";
            queryParams.dari = dari;
            queryParams.sampai = sampai;
        } else {
            queryParams.periode = periode;
            queryParams.tanggal = tanggal;
        }
        const query = new URLSearchParams(queryParams).toString();
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
                                onChange={(e) =>
                                    handlePeriodeChange(e.target.value)
                                }
                                className={inputClass}
                            >
                                <option value="harian">📅 Harian</option>
                                <option value="mingguan">🗓️ Mingguan</option>
                                <option value="semester">🎓 Semester</option>
                                <option value="rentang_bulanan">
                                    📆 Bulanan (Rentang Bulan)
                                </option>
                            </select>
                        </div>

                        {!["rentang", "rentang_bulanan", "semester"].includes(
                            periode,
                        ) && (
                            <div>
                                <label className="text-sm font-medium text-gray-700">
                                    Tanggal Acuan
                                </label>
                                <input
                                    type="date"
                                    value={tanggal}
                                    onChange={(e) => setTanggal(e.target.value)}
                                    className={inputClass}
                                />
                            </div>
                        )}

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
                    </div>

                    {/* ===== PANEL RENTANG (muncul untuk `rentang` dan `rentang_bulanan`) ===== */}
                    {["rentang", "rentang_bulanan"].includes(periode) && (
                        <div className="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                            <div className="mb-3 flex items-center gap-2">
                                <span className="text-lg">📆</span>
                                <h4 className="text-sm font-bold text-indigo-900">
                                    Pilih Rentang Bulan
                                </h4>
                            </div>

                            <select
                                value={rentangBulan}
                                onChange={(e) =>
                                    handleRentangChange(e.target.value)
                                }
                                className="w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="custom">
                                    ✏️ Custom (isi manual di bawah)
                                </option>
                                <option value="bulan_ini">📅 Bulan ini</option>
                                <option value="bulan_lalu">
                                    📅 Bulan lalu penuh
                                </option>
                                <option value="2_bulan">
                                    📅 2 bulan terakhir (60 hari)
                                </option>
                                <option value="3_bulan">
                                    📅 3 bulan terakhir (90 hari)
                                </option>
                                <option value="semester">
                                    🎓 Semester berjalan
                                </option>
                                <option value="1ags_2okt">
                                    📊 1 Agustus – 2 Oktober 2026
                                </option>
                            </select>

                            <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div>
                                    <label className="mb-1 block text-xs font-semibold text-gray-700">
                                        📅 Dari Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={dari}
                                        onChange={(e) => {
                                            setDari(e.target.value);
                                            setRentangBulan("custom");
                                        }}
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
                                        onChange={(e) => {
                                            setSampai(e.target.value);
                                            setRentangBulan("custom");
                                        }}
                                        className={inputClass}
                                    />
                                </div>
                            </div>

                            <div className="mt-3 flex items-center gap-3 rounded-md bg-white px-3 py-2 text-xs text-gray-700">
                                <span className="font-semibold text-indigo-700">
                                    Rentang aktif:
                                </span>
                                <span className="font-mono">{dari || "-"}</span>
                                <span className="text-gray-400">→</span>
                                <span className="font-mono">
                                    {sampai || "-"}
                                </span>
                            </div>
                        </div>
                    )}

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
                        >
                            {loading === "daftar-hadir"
                                ? "⏳ Menyiapkan..."
                                : "📋 Download Daftar Hadir Piket"}
                        </button>
                    </div>
                </div>

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
