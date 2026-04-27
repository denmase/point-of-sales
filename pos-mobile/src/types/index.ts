export interface Product {
  id: number;
  name: string;
  price: number;
  stock: number;
  barcode?: string;
  image?: string;
  category?: string;
}

export interface CartItem extends Product {
  quantity: number;
  subtotal: number;
}

export interface Customer {
  id: number;
  name: string;
  phone?: string;
  email?: string;
  address?: string;
  province?: string;
  city?: string;
  district?: string;
  village?: string;
}

export interface Transaction {
  id: number;
  invoice_number: string;
  customer?: Customer;
  items: CartItem[];
  subtotal: number;
  discount: number;
  shipping_cost: number;
  total: number;
  payment_method: 'cash' | 'transfer' | 'paylater';
  payment_status: 'pending' | 'paid' | 'unpaid';
  created_at: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  token: string;
}

export interface StoreProfile {
  name: string;
  logo?: string;
  address?: string;
  phone?: string;
  email?: string;
  city?: string;
}
