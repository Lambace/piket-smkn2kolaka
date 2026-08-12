import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const presetColors = [
    { name: "Indigo", value: "#4f46e5" },
    { name: "Biru", value: "#2563eb" },
    { name: "Hijau", value: "#16a34a" },
    { name: "Merah", value: "#dc2626" },
    { name: "Ungu", value: "#9333ea" },
    { name: "Oranye", value: "#ea580c" },
    { name: "Teal", value: "#0d9488" },
    { name: "Abu", value: "#475569" },
];

export default function Edit({ pengaturan }) {
    const { flash, errors } = usePage().props;

    // ===== Identitas =====
    const [namaSekolah, setNamaSekolah] = useState(
        pengaturan.nama_sekolah || "",
    );
    const [namaInstansi, setNamaInstansi] = useState(
        pengaturan.nama_instansi || "",
    );
    const [warnaTema, setWarnaTema] = useState(
        pengaturan.warna_tema || "#4f46e5",
    );

    // ===== File =====
    const [logo, setLogo] = useState(null);
    const [logoInstansi, setLogoInstansi] = useState(null);

    // ===== Kop Dokumen =====
    const [kopBaris1, setKopBaris1] = useState(pengaturan.kop_baris1 || "");
    const [kopBaris2, setKopBaris2] = useState(pengaturan.kop_baris2 || "");
    const [kopNamaSekolah, setKopNamaSekolah] = useState(
        pengaturan.kop_nama_sekolah || "",
    );
    const [alamat, setAlamat] = useState(pengaturan.alamat || "");
    const [telepon, setTelepon] = useState(pengaturan.telepon || "");
    const [email, setEmail] = useState(pengaturan.email || "");
    const [website, setWebsite] = useState(pengaturan.website || "");
    const [server, setServer] = useState(pengaturan.server || "");

    // ===== BARU: Data Tanda Tangan =====
    const [kepalaSekolah, setKepalaSekolah] = useState(
        pengaturan.kepala_sekolah || "",
    );
    const [nipKepalaSekolah, setNipKepalaSekolah] = useState(
        pengaturan.nip_kepala_sekolah || "",
    );
    const [koordinatorPiket, setKoordinatorPiket] = useState(
        pengaturan.koordinator_piket || "",
    );
    const [nipKoordinatorPiket, setNipKoordinatorPiket] = useState(
        pengaturan.nip_koordinator_piket || "",
    );

    const [processing, setProcessing] = useState(false);

    const submit = (e) => {
        e.preventDefault();

        const formData = new FormData();

        // Identitas
        formData.append("nama_sekolah", namaSekolah);
        formData.append("nama_instansi", namaInstansi);
        formData.append("warna_tema", warnaTema);

        // File
        if (logo) formData.append("logo", logo);
        if (logoInstansi) formData.append("logo_instansi", logoInstansi);

        // Kop Dokumen
        formData.append("kop_baris1", kopBaris1);
        formData.append("kop_baris2", kopBaris2);
        formData.append("kop_nama_sekolah", kopNamaSekolah);
        formData.append("alamat", alamat);
        formData.append("telepon", telepon);
        formData.append("email", email);
        formData.append("website", website);
        formData.append("server", server);

        // BARU: Data Tanda Tangan
        formData.append("kepala_sekolah", kepalaSekolah);
        formData.append("nip_kepala_sekolah", nipKepalaSekolah);
        formData.append("koordinator_piket", koordinatorPiket);
        formData.append("nip_koordinator_piket", nipKoordinatorPiket);

        setProcessing(true);
        router.post(route("pengaturan.update"), formData, {
            onSuccess: () => {
                setLogo(null);
                setLogoInstansi(null);
            },
            onFinish: () => setProcessing(false),
        });
    };

    const inputClass =
        "block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";
    const labelClass = "mb-1 block text-sm font-medium text-gray-700";

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    ⚙️ Pengaturan Aplikasi
                </h2>
            }
        >
            <Head title="Pengaturan" />

            <div className="mx-auto max-w-3xl space-y-6">
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

                <form
                    onSubmit={submit}
                    className="space-y-6 rounded-lg bg-white p-6 shadow"
                >
                    {/* ===== IDENTITAS SEKOLAH ===== */}
                    <div>
                        <h3 className="mb-3 text-base font-semibold text-gray-800">
                            🏫 Identitas Sekolah
                        </h3>
                        <div className="space-y-3">
                            <div>
                                <label className={labelClass}>
                                    Nama Sekolah{" "}
                                    <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={namaSekolah}
                                    onChange={(e) =>
                                        setNamaSekolah(e.target.value)
                                    }
                                    className={inputClass}
                                    required
                                />
                                {errors.nama_sekolah && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.nama_sekolah}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className={labelClass}>
                                    Nama Instansi{" "}
                                    <span className="text-xs text-gray-400">
                                        (opsional)
                                    </span>
                                </label>
                                <input
                                    type="text"
                                    value={namaInstansi}
                                    onChange={(e) =>
                                        setNamaInstansi(e.target.value)
                                    }
                                    placeholder="cth: Pemerintah Provinsi Sulawesi Tenggara"
                                    className={inputClass}
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Ditampilkan di bagian atas kop dokumen.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* ===== WARNA TEMA ===== */}
                    <div>
                        <h3 className="mb-3 text-base font-semibold text-gray-800">
                            🎨 Warna Tema
                        </h3>
                        <div className="flex flex-wrap items-center gap-3">
                            {presetColors.map((c) => (
                                <button
                                    type="button"
                                    key={c.value}
                                    title={c.name}
                                    onClick={() => setWarnaTema(c.value)}
                                    className={`h-9 w-9 rounded-full border-2 transition ${
                                        warnaTema === c.value
                                            ? "scale-110 border-gray-800"
                                            : "border-transparent hover:scale-105"
                                    }`}
                                    style={{ backgroundColor: c.value }}
                                ></button>
                            ))}
                            <input
                                type="color"
                                value={warnaTema}
                                onChange={(e) => setWarnaTema(e.target.value)}
                                className="h-9 w-12 cursor-pointer rounded border border-gray-300"
                                title="Warna kustom"
                            />
                            <span className="text-sm text-gray-500">
                                {warnaTema}
                            </span>
                        </div>
                        {errors.warna_tema && (
                            <p className="mt-1 text-xs text-red-600">
                                {errors.warna_tema}
                            </p>
                        )}
                    </div>

                    {/* ===== UPLOAD LOGO (2 KOLOM) ===== */}
                    <div>
                        <h3 className="mb-3 text-base font-semibold text-gray-800">
                            🖼️ Logo (Kop Laporan)
                        </h3>
                        <p className="mb-3 text-xs text-gray-500">
                            Logo instansi di kiri kop, logo sekolah di kanan
                            kop.
                        </p>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            {/* Logo Sekolah */}
                            <div className="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    🏫 Logo Sekolah{" "}
                                    <span className="text-red-500">*</span>
                                </label>
                                <div className="mb-3 flex items-start gap-3">
                                    {pengaturan.logo ? (
                                        <img
                                            src={`/storage/${pengaturan.logo}`}
                                            alt="Logo sekolah"
                                            className="h-16 w-16 rounded-lg border border-gray-200 bg-white object-contain p-1"
                                        />
                                    ) : (
                                        <div className="flex h-16 w-16 items-center justify-center rounded-lg bg-white text-3xl border border-gray-200">
                                            🏫
                                        </div>
                                    )}
                                    {logo && (
                                        <>
                                            <span className="self-center text-gray-400">
                                                →
                                            </span>
                                            <img
                                                src={URL.createObjectURL(logo)}
                                                alt="Preview baru"
                                                className="h-16 w-16 rounded-lg border-2 border-indigo-400 bg-white object-contain p-1"
                                            />
                                        </>
                                    )}
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setLogo(e.target.files[0])}
                                    className="block w-full text-xs text-gray-600 file:mr-2 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                                {errors.logo && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.logo}
                                    </p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">
                                    PNG/JPG/SVG maks 2MB
                                </p>
                            </div>

                            {/* Logo Instansi */}
                            <div className="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    🏛️ Logo Instansi{" "}
                                    <span className="text-xs text-gray-400">
                                        (opsional)
                                    </span>
                                </label>
                                <div className="mb-3 flex items-start gap-3">
                                    {pengaturan.logo_instansi ? (
                                        <img
                                            src={`/storage/${pengaturan.logo_instansi}`}
                                            alt="Logo instansi"
                                            className="h-16 w-16 rounded-lg border border-gray-200 bg-white object-contain p-1"
                                        />
                                    ) : (
                                        <div className="flex h-16 w-16 items-center justify-center rounded-lg bg-white text-3xl border border-gray-200">
                                            🏛️
                                        </div>
                                    )}
                                    {logoInstansi && (
                                        <>
                                            <span className="self-center text-gray-400">
                                                →
                                            </span>
                                            <img
                                                src={URL.createObjectURL(
                                                    logoInstansi,
                                                )}
                                                alt="Preview baru"
                                                className="h-16 w-16 rounded-lg border-2 border-indigo-400 bg-white object-contain p-1"
                                            />
                                        </>
                                    )}
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) =>
                                        setLogoInstansi(e.target.files[0])
                                    }
                                    className="block w-full text-xs text-gray-600 file:mr-2 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                                {errors.logo_instansi && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.logo_instansi}
                                    </p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">
                                    Logo Pemda, Dinas Pendidikan, Tut Wuri
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* ===== KOP DOKUMEN ===== */}
                    <div>
                        <h3 className="mb-3 text-base font-semibold text-gray-800">
                            📄 Teks Kop Laporan
                        </h3>
                        <p className="mb-3 text-xs text-gray-500">
                            Kosongkan untuk memakai teks default. Akan
                            ditampilkan di kop PDF laporan.
                        </p>

                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <label className={labelClass}>
                                    Baris 1 (Pemerintah)
                                </label>
                                <input
                                    type="text"
                                    value={kopBaris1}
                                    onChange={(e) =>
                                        setKopBaris1(e.target.value)
                                    }
                                    placeholder="PEMERINTAH PROVINSI SULAWESI TENGGARA"
                                    className={inputClass}
                                />
                            </div>

                            <div className="md:col-span-2">
                                <label className={labelClass}>
                                    Baris 2 (Dinas)
                                </label>
                                <input
                                    type="text"
                                    value={kopBaris2}
                                    onChange={(e) =>
                                        setKopBaris2(e.target.value)
                                    }
                                    placeholder="DINAS PENDIDIKAN DAN KEBUDAYAAN"
                                    className={inputClass}
                                />
                            </div>

                            {/* ===== NAMA SEKOLAH (KOP) - TEBAL ===== */}
                            <div className="md:col-span-2">
                                <label className={labelClass}>
                                    🏫 Nama Sekolah (Kop){" "}
                                    <span className="text-xs text-gray-400">
                                        (ditampilkan tebal di kop PDF)
                                    </span>
                                </label>
                                <input
                                    type="text"
                                    value={kopNamaSekolah}
                                    onChange={(e) =>
                                        setKopNamaSekolah(e.target.value)
                                    }
                                    placeholder={
                                        namaSekolah
                                            ? namaSekolah.toUpperCase()
                                            : "SEKOLAH MENENGAH KEJURUAN (SMK) NEGERI 2 KOLAKA"
                                    }
                                    className={`${inputClass} border-2 border-indigo-200 bg-indigo-50/30 p-2.5 font-bold uppercase tracking-wide`}
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    💡 Kosongkan untuk memakai nama sekolah
                                    utama. Preview di atas menampilkan format
                                    asli di kop PDF.
                                </p>
                                {errors.kop_nama_sekolah && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.kop_nama_sekolah}
                                    </p>
                                )}
                            </div>

                            <div className="md:col-span-2">
                                <label className={labelClass}>Alamat</label>
                                <input
                                    type="text"
                                    value={alamat}
                                    onChange={(e) => setAlamat(e.target.value)}
                                    placeholder="Jln. Poros Kolaka - Pomalaa KM. 16 Kec. Baula Kab. Kolaka"
                                    className={inputClass}
                                />
                            </div>

                            <div>
                                <label className={labelClass}>Telepon</label>
                                <input
                                    type="text"
                                    value={telepon}
                                    onChange={(e) => setTelepon(e.target.value)}
                                    placeholder="01234567890"
                                    className={inputClass}
                                />
                            </div>

                            <div>
                                <label className={labelClass}>Email</label>
                                <input
                                    type="text"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="smknsatubaula@yahoo.co.id"
                                    className={inputClass}
                                />
                            </div>

                            <div>
                                <label className={labelClass}>Website</label>
                                <input
                                    type="text"
                                    value={website}
                                    onChange={(e) => setWebsite(e.target.value)}
                                    placeholder="www.smk1baula.sch.id"
                                    className={inputClass}
                                />
                            </div>

                            <div>
                                <label className={labelClass}>Server</label>
                                <input
                                    type="text"
                                    value={server}
                                    onChange={(e) => setServer(e.target.value)}
                                    placeholder="sisfo.smk1baula.sch.id"
                                    className={inputClass}
                                />
                            </div>
                        </div>
                    </div>

                    {/* ===== BARU: DATA TANDA TANGAN ===== */}
                    <div>
                        <h3 className="mb-3 text-base font-semibold text-gray-800">
                            🖋️ Data Tanda Tangan (PDF Laporan)
                        </h3>
                        <p className="mb-3 text-xs text-gray-500">
                            Data ini akan ditampilkan di blok tanda tangan PDF
                            (Kepala Sekolah & Koordinator Piket). Kosongkan
                            untuk memakai titik-titik.
                        </p>

                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            {/* Kepala Sekolah */}
                            <div className="md:col-span-2">
                                <div className="rounded-lg border border-blue-200 bg-blue-50/30 p-4">
                                    <h4 className="mb-2 text-sm font-semibold text-blue-900">
                                        👤 Kepala Sekolah (Kiri)
                                    </h4>
                                    <div className="space-y-3">
                                        <div>
                                            <label className={labelClass}>
                                                Nama Lengkap
                                            </label>
                                            <input
                                                type="text"
                                                value={kepalaSekolah}
                                                onChange={(e) =>
                                                    setKepalaSekolah(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Drs. Ahmad Yani, M.Pd"
                                                className={inputClass}
                                            />
                                            {errors.kepala_sekolah && (
                                                <p className="mt-1 text-xs text-red-600">
                                                    {errors.kepala_sekolah}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <label className={labelClass}>
                                                NIP
                                            </label>
                                            <input
                                                type="text"
                                                value={nipKepalaSekolah}
                                                onChange={(e) =>
                                                    setNipKepalaSekolah(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="196501011990031001"
                                                maxLength={30}
                                                className={`${inputClass} font-mono`}
                                            />
                                            {errors.nip_kepala_sekolah && (
                                                <p className="mt-1 text-xs text-red-600">
                                                    {errors.nip_kepala_sekolah}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Koordinator Piket */}
                            <div className="md:col-span-2">
                                <div className="rounded-lg border border-purple-200 bg-purple-50/30 p-4">
                                    <h4 className="mb-2 text-sm font-semibold text-purple-900">
                                        👥 Koordinator Piket (Kanan)
                                    </h4>
                                    <div className="space-y-3">
                                        <div>
                                            <label className={labelClass}>
                                                Nama Lengkap
                                            </label>
                                            <input
                                                type="text"
                                                value={koordinatorPiket}
                                                onChange={(e) =>
                                                    setKoordinatorPiket(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Agussalim Tajuddin, S.Pd"
                                                className={inputClass}
                                            />
                                            {errors.koordinator_piket && (
                                                <p className="mt-1 text-xs text-red-600">
                                                    {errors.koordinator_piket}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <label className={labelClass}>
                                                NIP
                                            </label>
                                            <input
                                                type="text"
                                                value={nipKoordinatorPiket}
                                                onChange={(e) =>
                                                    setNipKoordinatorPiket(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="197501012000031001"
                                                maxLength={30}
                                                className={`${inputClass} font-mono`}
                                            />
                                            {errors.nip_koordinator_piket && (
                                                <p className="mt-1 text-xs text-red-600">
                                                    {
                                                        errors.nip_koordinator_piket
                                                    }
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p className="mt-3 text-xs text-gray-500">
                            💡 Jika dikosongkan, PDF akan menampilkan
                            titik-titik (siap ditulis tangan). Jika diisi, nama
                            & NIP akan terisi otomatis dengan garis bawah.
                        </p>
                    </div>

                    {/* Tombol Simpan */}
                    <button
                        type="submit"
                        disabled={processing}
                        style={{ backgroundColor: warnaTema }}
                        className="rounded-md px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                    >
                        {processing ? "Menyimpan..." : "💾 Simpan Pengaturan"}
                    </button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
