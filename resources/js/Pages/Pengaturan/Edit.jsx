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
    const [namaSekolah, setNamaSekolah] = useState(pengaturan.nama_sekolah);
    const [warnaTema, setWarnaTema] = useState(pengaturan.warna_tema);
    const [logo, setLogo] = useState(null);
    const [processing, setProcessing] = useState(false);

    const submit = (e) => {
        e.preventDefault();

        // Kirim data secara eksplisit dengan FormData
        const formData = new FormData();
        formData.append("nama_sekolah", namaSekolah);
        formData.append("warna_tema", warnaTema);
        if (logo) {
            formData.append("logo", logo);
        }

        setProcessing(true);
        router.post(route("pengaturan.update"), formData, {
            onSuccess: () => setLogo(null),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Pengaturan Aplikasi
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
                    {/* Nama Sekolah */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">
                            Nama Sekolah
                        </label>
                        <input
                            type="text"
                            value={namaSekolah}
                            onChange={(e) => setNamaSekolah(e.target.value)}
                            className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        {errors.nama_sekolah && (
                            <p className="mt-1 text-xs text-red-600">
                                {errors.nama_sekolah}
                            </p>
                        )}
                    </div>

                    {/* Warna Tema */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Warna Tema
                        </label>
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

                    {/* Logo */}
                    <div>
                        <label className="mb-2 block text-sm font-medium text-gray-700">
                            Logo Sekolah
                        </label>
                        <div className="flex flex-wrap items-center gap-4">
                            {pengaturan.logo ? (
                                <img
                                    src={`/storage/${pengaturan.logo}`}
                                    alt="Logo lama"
                                    className="h-16 w-16 rounded-lg border border-gray-200 bg-white object-contain p-1"
                                />
                            ) : (
                                <div className="flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 text-3xl">
                                    🏫
                                </div>
                            )}
                            {logo && (
                                <img
                                    src={URL.createObjectURL(logo)}
                                    alt="Preview logo baru"
                                    className="h-16 w-16 rounded-lg border border-gray-200 bg-white object-contain p-1"
                                />
                            )}
                            <input
                                type="file"
                                accept="image/*"
                                onChange={(e) => setLogo(e.target.files[0])}
                                className="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200"
                            />
                        </div>
                        {errors.logo && (
                            <p className="mt-1 text-xs text-red-600">
                                {errors.logo}
                            </p>
                        )}
                        <p className="mt-1 text-xs text-gray-500">
                            Format PNG/JPG/SVG maks 2MB. Kosongkan jika tidak
                            ingin mengganti logo.
                        </p>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        style={{ backgroundColor: warnaTema }}
                        className="rounded-md px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                    >
                        {processing ? "Menyimpan..." : "Simpan Pengaturan"}
                    </button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
