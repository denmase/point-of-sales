import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { User, Product, Customer, Transaction } from '../types';

const API_BASE_URL = 'http://192.168.1.100:8000/api'; // Ganti dengan IP server Anda

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor untuk menambahkan token
api.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

export const authService = {
  login: async (email: string, password: string) => {
    const response = await api.post('/login', { email, password });
    if (response.data.token) {
      await AsyncStorage.setItem('auth_token', response.data.token);
      await AsyncStorage.setItem('user', JSON.stringify(response.data.user));
    }
    return response.data;
  },

  logout: async () => {
    await AsyncStorage.removeItem('auth_token');
    await AsyncStorage.removeItem('user');
  },

  getToken: async () => {
    return await AsyncStorage.getItem('auth_token');
  },

  getUser: async () => {
    const user = await AsyncStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },
};

export const productService = {
  getAll: async () => {
    const response = await api.get('/products');
    return response.data;
  },

  search: async (query: string) => {
    const response = await api.get(`/products/search?q=${query}`);
    return response.data;
  },

  getByBarcode: async (barcode: string) => {
    const response = await api.get(`/products/barcode/${barcode}`);
    return response.data;
  },
};

export const customerService = {
  getAll: async () => {
    const response = await api.get('/customers');
    return response.data;
  },

  create: async (data: Partial<Customer>) => {
    const response = await api.post('/customers', data);
    return response.data;
  },

  getById: async (id: number) => {
    const response = await api.get(`/customers/${id}`);
    return response.data;
  },
};

export const transactionService = {
  create: async (data: {
    customer_id?: number;
    items: Array<{ product_id: number; quantity: number; price: number }>;
    discount: number;
    shipping_cost: number;
    payment_method: string;
  }) => {
    const response = await api.post('/transactions', data);
    return response.data;
  },

  getAll: async () => {
    const response = await api.get('/transactions');
    return response.data;
  },

  getById: async (id: number) => {
    const response = await api.get(`/transactions/${id}`);
    return response.data;
  },

  getInvoice: async (invoiceNumber: string) => {
    const response = await api.get(`/transactions/invoice/${invoiceNumber}`);
    return response.data;
  },
};

export default api;
