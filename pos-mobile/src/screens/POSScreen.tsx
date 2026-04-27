import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  FlatList,
  StyleSheet,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { productService } from '../../services/api';
import { useCart } from '../../context/CartContext';
import { Product } from '../../types';

interface POSScreenProps {
  navigation: any;
}

export const POSScreen: React.FC<POSScreenProps> = ({ navigation }) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const { addToCart, items, totalItems, subtotal } = useCart();

  useEffect(() => {
    loadProducts();
  }, []);

  const loadProducts = async () => {
    try {
      setLoading(true);
      // Menggunakan data mock untuk demo - ganti dengan API call saat production
      const mockProducts: Product[] = [
        { id: 1, name: 'Kopi Hitam', price: 15000, stock: 100, barcode: '8991234567890' },
        { id: 2, name: 'Teh Manis', price: 10000, stock: 150, barcode: '8991234567891' },
        { id: 3, name: 'Nasi Goreng', price: 25000, stock: 50, barcode: '8991234567892' },
        { id: 4, name: 'Mie Ayam', price: 20000, stock: 75, barcode: '8991234567893' },
        { id: 5, name: 'Es Jeruk', price: 12000, stock: 120, barcode: '8991234567894' },
        { id: 6, name: 'Ayam Bakar', price: 35000, stock: 40, barcode: '8991234567895' },
        { id: 7, name: 'Soto Ayam', price: 22000, stock: 60, barcode: '8991234567896' },
        { id: 8, name: 'Bakso Urat', price: 18000, stock: 80, barcode: '8991234567897' },
      ];
      setProducts(mockProducts);
      
      // Uncomment untuk menggunakan API real:
      // const data = await productService.getAll();
      // setProducts(data);
    } catch (error) {
      console.error('Error loading products:', error);
    } finally {
      setLoading(false);
    }
  };

  const filteredProducts = products.filter((product) =>
    product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    product.barcode?.includes(searchQuery)
  );

  const handleAddToCart = (product: Product) => {
    if (product.stock <= 0) {
      Alert.alert('Stok Habis', `${product.name} sedang tidak tersedia`);
      return;
    }
    addToCart(product);
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const renderProductItem = ({ item }: { item: Product }) => (
    <TouchableOpacity
      style={[styles.productCard, item.stock <= 0 && styles.productCardDisabled]}
      onPress={() => handleAddToCart(item)}
      disabled={item.stock <= 0}
    >
      <View style={styles.productInfo}>
        <Text style={styles.productName}>{item.name}</Text>
        <Text style={styles.productPrice}>{formatCurrency(item.price)}</Text>
        <Text style={[styles.productStock, item.stock <= 0 && styles.stockEmpty]}>
          Stok: {item.stock}
        </Text>
      </View>
      <TouchableOpacity
        style={[styles.addButton, item.stock <= 0 && styles.addButtonDisabled]}
        onPress={() => handleAddToCart(item)}
        disabled={item.stock <= 0}
      >
        <Text style={styles.addButtonText}>+</Text>
      </TouchableOpacity>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Point of Sales</Text>
        <TouchableOpacity
          style={styles.cartButton}
          onPress={() => navigation.navigate('Cart')}
        >
          <Text style={styles.cartButtonText}>🛒</Text>
          {totalItems > 0 && (
            <View style={styles.cartBadge}>
              <Text style={styles.cartBadgeText}>{totalItems}</Text>
            </View>
          )}
        </TouchableOpacity>
      </View>

      {/* Search Bar */}
      <View style={styles.searchContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder="Cari produk atau scan barcode..."
          value={searchQuery}
          onChangeText={setSearchQuery}
        />
      </View>

      {/* Products List */}
      {loading ? (
        <ActivityIndicator size="large" color="#2563eb" style={{ marginTop: 40 }} />
      ) : (
        <FlatList
          data={filteredProducts}
          renderItem={renderProductItem}
          keyExtractor={(item) => item.id.toString()}
          numColumns={2}
          contentContainerStyle={styles.productsList}
          columnWrapperStyle={styles.productsRow}
        />
      )}

      {/* Bottom Cart Summary */}
      {totalItems > 0 && (
        <TouchableOpacity
          style={styles.bottomCart}
          onPress={() => navigation.navigate('Cart')}
        >
          <View>
            <Text style={styles.bottomCartLabel}>{totalItems} item(s)</Text>
            <Text style={styles.bottomCartTotal}>{formatCurrency(subtotal)}</Text>
          </View>
          <Text style={styles.bottomCartAction}>Lihat Keranjang →</Text>
        </TouchableOpacity>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    paddingTop: 60,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e5e5',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#333',
  },
  cartButton: {
    position: 'relative',
    padding: 8,
  },
  cartButtonText: {
    fontSize: 28,
  },
  cartBadge: {
    position: 'absolute',
    top: 0,
    right: 0,
    backgroundColor: '#ef4444',
    borderRadius: 10,
    width: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  cartBadgeText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: 'bold',
  },
  searchContainer: {
    padding: 16,
    backgroundColor: '#fff',
  },
  searchInput: {
    backgroundColor: '#f5f5f5',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
  },
  productsList: {
    padding: 16,
    paddingBottom: 100,
  },
  productsRow: {
    gap: 12,
    marginBottom: 12,
  },
  productCard: {
    flex: 1,
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  productCardDisabled: {
    opacity: 0.5,
  },
  productInfo: {
    flex: 1,
  },
  productName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    marginBottom: 4,
  },
  productPrice: {
    fontSize: 14,
    color: '#2563eb',
    fontWeight: 'bold',
    marginBottom: 4,
  },
  productStock: {
    fontSize: 12,
    color: '#666',
  },
  stockEmpty: {
    color: '#ef4444',
  },
  addButton: {
    backgroundColor: '#2563eb',
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  addButtonDisabled: {
    backgroundColor: '#ccc',
  },
  addButtonText: {
    color: '#fff',
    fontSize: 20,
    fontWeight: 'bold',
  },
  bottomCart: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: '#2563eb',
    padding: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
  },
  bottomCartLabel: {
    color: '#fff',
    fontSize: 14,
    opacity: 0.9,
  },
  bottomCartTotal: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  bottomCartAction: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
});
