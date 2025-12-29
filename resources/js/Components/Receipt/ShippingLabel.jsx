import React from "react";
import {
    IconTruck,
    IconMapPin,
    IconPhone,
    IconUser,
    IconPackage,
} from "@tabler/icons-react";

/**
 * Shipping Label Component
 * Size: 150x100mm for standard shipping labels
 */
export default function ShippingLabel({ transaction, store = {} }) {
    const formatPrice = (price = 0) =>
        price.toLocaleString("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        });

    const formatDate = (value) => {
        if (!value) return "-";
        const d = new Date(value);
        return d.toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
    };

    const handlePrint = () => {
        window.print();
    };

    const SimpleBarcode = ({ value }) => {
        const bars = (value || "").split("").map((char, idx) => {
            const weight = (char.charCodeAt(0) + idx * 17) % 4;
            return 2 + weight;
        });

        return (
            <div className="flex items-end gap-[2px] mt-1 justify-end">
                {bars.map((w, i) => (
                    <span
                        key={i}
                        style={{ width: `${w}px` }}
                        className="h-8 bg-black block"
                    />
                ))}
            </div>
        );
    };

    const storeName = store?.name || "TOKO";
    const storeInitial = storeName?.[0] || "T";
    const storeLogo = store?.logo;

    // Get customer details
    const customer = transaction?.customer || {};
    const hasCustomer = customer?.name;

    return (
        <>
            {/* Print Styles */}
            <style>
                {`
                    @media print {
                        @page {
                            size: 160mm 110mm;
                            margin: 0;
                        }
                        body {
                            margin: 0;
                            padding: 0;
                        }
                        .shipping-label {
                            width: 150mm !important;
                            height: 100mm !important;
                            page-break-after: always;
                        }
                        .no-print {
                            display: none !important;
                        }
                    }
                `}
            </style>

            {/* Shipping Label */}
            <div
                className="shipping-label bg-white border-2 border-slate-300 p-5"
                style={{ width: "160mm", height: "110mm" }}
            >
                {/* Header with store profile */}
                <div className="flex items-start justify-between border-b border-slate-200 pb-3 mb-3">
                    <div className="flex items-start gap-3">
                        <div className="w-14 h-14 border border-slate-200 bg-white flex items-center justify-center p-1">
                            {storeLogo ? (
                                <img
                                    src={storeLogo}
                                    alt={storeName}
                                    className="max-w-full max-h-full object-contain"
                                />
                            ) : (
                                <span className="text-lg font-bold text-primary-600">
                                    {storeInitial}
                                </span>
                            )}
                        </div>
                        <div className="space-y-1">
                            <p className="text-lg font-bold text-slate-800">
                                {storeName}
                            </p>
                            {store.address && (
                                <p className="text-xs text-slate-600 leading-snug">
                                    {store.address}
                                </p>
                            )}
                            <div className="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-600">
                                {store.phone && (
                                    <span className="flex items-center gap-1">
                                        <IconPhone size={12} />
                                        {store.phone}
                                    </span>
                                )}
                                {store.email && <span>{store.email}</span>}
                                {store.website && <span>{store.website}</span>}
                            </div>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-xs text-slate-500">Invoice</p>
                        <p className="text-base font-bold text-slate-800">
                            {transaction?.invoice || "-"}
                        </p>
                        <p className="text-xs text-slate-500 mt-1">
                            {formatDate(transaction?.created_at)}
                        </p>
                    </div>
                </div>

                {/* Main Content */}
                <div className="grid grid-cols-2 gap-4">
                    {/* Penerima */}
                    <div className="border border-slate-200 rounded-lg p-3">
                        <div className="flex items-center gap-2 mb-2">
                            <IconUser size={16} className="text-slate-500" />
                            <span className="text-xs font-semibold text-slate-600 uppercase">
                                Penerima
                            </span>
                        </div>
                        {hasCustomer ? (
                            <>
                                <p className="text-lg font-bold text-slate-800">
                                    {customer.name}
                                </p>
                                {customer.phone && (
                                    <p className="text-sm text-slate-600 flex items-center gap-1 mt-1">
                                        <IconPhone size={14} />
                                        {customer.phone}
                                    </p>
                                )}
                                {customer.address && (
                                    <p className="text-sm text-slate-600 flex items-start gap-1 mt-1">
                                        <IconMapPin
                                            size={14}
                                            className="mt-0.5 flex-shrink-0"
                                        />
                                        <span>{customer.address}</span>
                                    </p>
                                )}
                            </>
                        ) : (
                            <p className="text-sm text-slate-400 italic">
                                Pelanggan umum
                            </p>
                        )}
                    </div>

                    {/* Detail Order */}
                    <div className="border border-slate-200 rounded-lg p-3">
                        <div className="flex items-center gap-2 mb-2">
                            <IconPackage size={16} className="text-slate-500" />
                            <span className="text-xs font-semibold text-slate-600 uppercase">
                                Detail Order
                            </span>
                        </div>
                        <div className="space-y-1.5">
                            <div className="flex justify-between text-sm">
                                <span className="text-slate-500">Tanggal:</span>
                                <span className="font-medium text-slate-700">
                                    {formatDate(transaction?.created_at)}
                                </span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-slate-500">
                                    Jumlah Item:
                                </span>
                                <span className="font-medium text-slate-700">
                                    {transaction?.details?.length || 0} item
                                </span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-slate-500">Total:</span>
                                <span className="font-bold text-primary-600">
                                    {formatPrice(transaction?.grand_total)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Product List */}
                <div className="mt-3 border border-slate-200 rounded-lg p-2">
                    <p className="text-xs font-semibold text-slate-600 mb-1">
                        Produk:
                    </p>
                    <div className="text-xs text-slate-600 line-clamp-2">
                        {transaction?.details
                            ?.map(
                                (item) =>
                                    `${item.product?.title || "Produk"} (${
                                        item.qty
                                    }x)`
                            )
                            .join(", ") || "-"}
                    </div>
                </div>

                {/* Footer */}
                <div className="mt-3 pt-3 border-t border-dashed border-slate-300">
                    <div className="flex justify-between items-center">
                        <p className="text-xs text-slate-400">
                            Kasir: {transaction?.cashier?.name || "-"}
                        </p>
                        <p className="text-xs text-slate-400">
                            Dicetak: {new Date().toLocaleDateString("id-ID")}
                        </p>
                    </div>
                    <div className="flex justify-end mt-1">
                        <div className="inline-flex flex-col items-end">
                            <SimpleBarcode value={transaction?.invoice} />
                            <span className="text-[11px] text-slate-500 mt-0.5">
                                {transaction?.invoice}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
