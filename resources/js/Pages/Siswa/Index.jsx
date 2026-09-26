import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const emptyForm = {
    nisn: "",
    nis: "",
    nama: "",
    kelas: "",
    jurusan: "",
    jenis_kelamin: "",
    alamat: "",
    telepon: "",
};

export default function SiswaIndex(props) {
    // ===== ANTI-CRASH: default aman untuk semua prop =====
    const siswa = props.siswa ?? {
        data: [],
        current_page: 1,
        last_page: 1,
        total: 0,
        prev_page_url: null,
        next_page_url: null,
    };
    const list = Array.isArray(siswa.data) ? siswa.data : [];

    const filters = props.filters ?? {};
    const kelasOptions = Array.isArray(filters.kelas) ? filters.kelas : [];
    const jurusanOptions = Array.isArray(filters.jurusan)
        ? filters.jurusan
        : [];

    // params bisa array atau objek, pastikan jadi objek
    const params =
        props.params &&
        typeof props.params === "object" &&
        !Array.isArray(props.params)
            ? props.params
            : {};

    const { flash, errors } = usePage().props;

    // ===== State =====
    const [search, setSearch] = useState(params.search ?? "");
    const [filterKelas, setFilterKelas] = useState(params.kelas ?? "");
    const [filterJurusan, setFilterJurusan] = useState(params.jurusan ?? "");
    const [filterJK, setFilterJK] = useState(params.jenis_kelamin ?? "");
    const [sort, setSort] = useState(params.sort ?? "nama");
    const [direction, setDirection] = useState(params.direction ?? "asc");

    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [showImport, setShowImport] = useState(false);
    const [importFile, setImportFile] = useState(null);

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    const reload = (overrides = {}) => {
        router.get(
            route("siswa.index"),
            {
                search: search || undefined,
                kelas: filterKelas || undefined,
                jurusan: filterJurusan || undefined,
                jenis_kelamin: filterJK || undefined,
                sort,
                direction,
                ...overrides,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const onSearch = (v) => {
        setSearch(v);
        reload({ search: v || undefined });
    };

    const toggleSort = (col) => {
        const newDir = sort === col && direction === "asc" ? "desc" : "asc";
        setSort(col);
        setDirection(newDir);
        reload({ sort: col, direction: newDir });
    };

    const resetFilters = () => {
        setSearch("");
        setFilterKelas("");
        setFilterJurusan("");
        setFilterJK("");
        setSort("nama");
        setDirection("asc");
        router.get(route("siswa.index"));
    };

    const handleChange = (e) =>
        setForm({ ...form, [e.target.name]: e.target.value });

    const submit = (e) => {
        e.preventDefault();
        if (editingId) {
            router.put(route("siswa.update", editingId), form, {
                onSuccess: () => {
                    setEditingId(null);
                    setShowForm(false);
                    setForm(emptyForm);
                },
            });
        } else {
            router.post(route("siswa.store"), form, {
                onSuccess: () => {
                    setShowForm(false);
                    setForm(emptyForm);
                },
            });
        }
    };

    const submitImport = (e) => {
        e.preventDefault();
        if (!importFile) return;
        const fd = new FormData();
        fd.append("file", importFile);
        router.post(route("siswa.import"), fd, {
            onSuccess: () => {
                setShowImport(false);
                setImportFile(null);
            },
        });
    };

    const startEdit = (s) => {
        setEditingId(s.id);
        setForm({
            nisn: s.nisn ?? "",
            nis: s.nis ?? "",
            nama: s.nama ?? "",
            kelas: s.kelas ?? "",
            jurusan: s.jurusan ?? "",
            jenis_kelamin: s.jenis_kelamin ?? "",
            alamat: s.alamat ?? "",
            telepon: s.telepon ?? "",
        });
        setShowForm(true);
    };

    const remove = (id) => {
        if (confirm("Yakin ingin menghapus data siswa ini?"))
            router.delete(route("siswa.destroy", id));
    };

    const sortIcon = (col) =>
        sort === col ? (direction === "asc" ? " ↑" : " ↓") : "";

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Data Siswa
                </h2>
            }
        >
            <Head title="Data Siswa" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-md bg-green-100 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-md bg-red-100 p-3 text-sm text-red-700">
                        {flash.error}
                    </div>
                )}

                {/* Toolbar */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <input
                        type="text"
                        placeholder="Cari nama / NISN / kelas..."
                        value={search}
                        onChange={(e) => onSearch(e.target.value)}
                        className="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <div className="flex gap-2">
                        <a
                            href={route("siswa.export")}
                            className="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                        >
                            ⬇ Export XLSX
                        </a>
                        <button
                            onClick={() => setShowImport(!showImport)}
                            className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            ⬆ Import
                        </button>
                        <button
                            onClick={() => {
                                setShowForm(!showForm);
                                setEditingId(null);
                                setForm(emptyForm);
                            }}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            {showForm ? "Tutup Form" : "+ Tambah Siswa"}
                        </button>
                    </div>
                </div>

                {/* Filter */}
                <div className="flex flex-wrap items-center gap-3 rounded-lg bg-white p-4 shadow">
                    <span className="text-sm font-semibold text-gray-600">
                        Filter:
                    </span>
                    <select
                        value={filterKelas}
                        onChange={(e) => {
                            setFilterKelas(e.target.value);
                            reload({ kelas: e.target.value || undefined });
                        }}
                        className="rounded-md border-gray-300 text-sm shadow-sm"
                    >
                        <option value="">Semua Kelas</option>
                        {kelasOptions.map((k) => (
                            <option key={k} value={k}>
                                {k}
                            </option>
                        ))}
                    </select>
                    <select
                        value={filterJurusan}
                        onChange={(e) => {
                            setFilterJurusan(e.target.value);
                            reload({ jurusan: e.target.value || undefined });
                        }}
                        className="rounded-md border-gray-300 text-sm shadow-sm"
                    >
                        <option value="">Semua Jurusan</option>
                        {jurusanOptions.map((j) => (
                            <option key={j} value={j}>
                                {j}
                            </option>
                        ))}
                    </select>
                    <select
                        value={filterJK}
                        onChange={(e) => {
                            setFilterJK(e.target.value);
                            reload({
                                jenis_kelamin: e.target.value || undefined,
                            });
                        }}
                        className="rounded-md border-gray-300 text-sm shadow-sm"
                    >
                        <option value="">Semua JK</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                    <button
                        onClick={resetFilters}
                        className="rounded-md bg-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-300"
                    >
                        ↺ Reset
                    </button>
                    <span className="ml-auto text-xs text-gray-500">
                        💡 Klik judul kolom untuk mengurutkan
                    </span>
                </div>

                {/* Form Import */}
                {showImport && (
                    <form
                        onSubmit={submitImport}
                        className="space-y-3 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            Import Data Siswa
                        </h3>
                        <p className="text-sm text-gray-500">
                            Format: <b>XLSX</b> atau CSV. Gunakan{" "}
                            <b>Export XLSX</b> sebagai template.
                        </p>
                        <input
                            type="file"
                            accept=".csv,.xlsx"
                            onChange={(e) => setImportFile(e.target.files[0])}
                            className="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-600"
                        />
                        {errors?.file && (
                            <p className="text-xs text-red-600">
                                {errors.file}
                            </p>
                        )}
                        <button className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Import Sekarang
                        </button>
                    </form>
                )}

                {/* Form Tambah/Edit */}
                {showForm && (
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            {editingId ? "Edit Siswa" : "Tambah Siswa"}
                        </h3>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label className="text-sm text-gray-600">
                                    NISN
                                </label>
                                <input
                                    name="nisn"
                                    value={form.nisn}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                                {errors?.nisn && (
                                    <p className="text-xs text-red-600">
                                        {errors.nisn}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    NIS (opsional)
                                </label>
                                <input
                                    name="nis"
                                    value={form.nis}
                                    onChange={handleChange}
                                    className={inputClass}
                                />
                                {errors?.nis && (
                                    <p className="text-xs text-red-600">
                                        {errors.nis}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Nama Lengkap
                                </label>
                                <input
                                    name="nama"
                                    value={form.nama}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                                {errors?.nama && (
                                    <p className="text-xs text-red-600">
                                        {errors.nama}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Kelas
                                </label>
                                <input
                                    name="kelas"
                                    value={form.kelas}
                                    onChange={handleChange}
                                    placeholder="XII RPL 1"
                                    className={inputClass}
                                    required
                                />
                                {errors?.kelas && (
                                    <p className="text-xs text-red-600">
                                        {errors.kelas}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Jurusan
                                </label>
                                <input
                                    name="jurusan"
                                    value={form.jurusan}
                                    onChange={handleChange}
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Jenis Kelamin
                                </label>
                                <select
                                    name="jenis_kelamin"
                                    value={form.jenis_kelamin}
                                    onChange={handleChange}
                                    className={inputClass}
                                >
                                    <option value="">- Tidak diisi -</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Telepon
                                </label>
                                <input
                                    name="telepon"
                                    value={form.telepon}
                                    onChange={handleChange}
                                    className={inputClass}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm text-gray-600">
                                    Alamat
                                </label>
                                <textarea
                                    name="alamat"
                                    value={form.alamat}
                                    onChange={handleChange}
                                    className={inputClass}
                                    rows="1"
                                ></textarea>
                            </div>
                        </div>
                        <button className="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            {editingId ? "Update Data" : "Simpan Data"}
                        </button>
                    </form>
                )}

                {/* Tabel */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th
                                    className="cursor-pointer select-none p-3 hover:bg-gray-100"
                                    onClick={() => toggleSort("nisn")}
                                >
                                    NISN{sortIcon("nisn")}
                                </th>
                                <th
                                    className="cursor-pointer select-none p-3 hover:bg-gray-100"
                                    onClick={() => toggleSort("nama")}
                                >
                                    Nama{sortIcon("nama")}
                                </th>
                                <th
                                    className="cursor-pointer select-none p-3 hover:bg-gray-100"
                                    onClick={() => toggleSort("kelas")}
                                >
                                    Kelas{sortIcon("kelas")}
                                </th>
                                <th
                                    className="cursor-pointer select-none p-3 hover:bg-gray-100"
                                    onClick={() => toggleSort("jenis_kelamin")}
                                >
                                    JK{sortIcon("jenis_kelamin")}
                                </th>
                                <th className="p-3">Telepon</th>
                                <th className="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="6"
                                        className="p-4 text-center text-gray-500"
                                    >
                                        Tidak ada data yang cocok dengan filter.
                                    </td>
                                </tr>
                            ) : (
                                list.map((s) => (
                                    <tr
                                        key={s.id}
                                        className="border-t border-gray-200"
                                    >
                                        <td className="p-3">{s.nisn}</td>
                                        <td className="p-3 font-medium">
                                            {s.nama}
                                        </td>
                                        <td className="p-3">{s.kelas}</td>
                                        <td className="p-3">
                                            {s.jenis_kelamin || "-"}
                                        </td>
                                        <td className="p-3">
                                            {s.telepon ?? "-"}
                                        </td>
                                        <td className="p-3 text-center">
                                            <button
                                                onClick={() => startEdit(s)}
                                                className="mr-2 rounded bg-yellow-500 px-2 py-1 text-xs font-semibold text-white hover:bg-yellow-600"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                onClick={() => remove(s.id)}
                                                className="rounded bg-red-500 px-2 py-1 text-xs font-semibold text-white hover:bg-red-600"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="flex items-center justify-between">
                    <div>
                        {siswa.prev_page_url && (
                            <button
                                onClick={() => router.get(siswa.prev_page_url)}
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {siswa.current_page} dari {siswa.last_page} •
                        Total {siswa.total} siswa
                    </span>
                    <div>
                        {siswa.next_page_url && (
                            <button
                                onClick={() => router.get(siswa.next_page_url)}
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                Berikutnya →
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
