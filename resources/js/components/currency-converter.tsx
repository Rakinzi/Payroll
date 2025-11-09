import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { ArrowLeftRight, RefreshCw, TrendingUp, TrendingDown } from 'lucide-react';
import { useActiveCurrencies, getExchangeRate, convertCurrency } from '@/hooks/queries/use-currencies';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';

export default function CurrencyConverter() {
    const { data: currencies, isLoading } = useActiveCurrencies();

    const [fromCurrency, setFromCurrency] = useState<string>('');
    const [toCurrency, setToCurrency] = useState<string>('');
    const [amount, setAmount] = useState<string>('1.00');
    const [convertedAmount, setConvertedAmount] = useState<number | null>(null);
    const [exchangeRate, setExchangeRate] = useState<number | null>(null);
    const [isConverting, setIsConverting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Set default currencies when currencies are loaded
    useEffect(() => {
        if (currencies && currencies.length >= 2) {
            // Try to set ZWG and USD as defaults if available
            const zwg = currencies.find((c) => c.code === 'ZWG');
            const usd = currencies.find((c) => c.code === 'USD');

            if (zwg && usd) {
                setFromCurrency('ZWG');
                setToCurrency('USD');
            } else {
                // Otherwise use first two available currencies
                setFromCurrency(currencies[0].code);
                setToCurrency(currencies[1].code);
            }
        }
    }, [currencies]);

    // Perform conversion when inputs change
    useEffect(() => {
        if (fromCurrency && toCurrency && amount && parseFloat(amount) > 0) {
            performConversion();
        }
    }, [fromCurrency, toCurrency, amount]);

    // Perform the conversion
    const performConversion = async () => {
        if (!fromCurrency || !toCurrency || !amount || parseFloat(amount) <= 0) {
            setConvertedAmount(null);
            setExchangeRate(null);
            return;
        }

        setIsConverting(true);
        setError(null);

        try {
            // Get exchange rate
            const rate = await getExchangeRate(fromCurrency, toCurrency);
            setExchangeRate(rate);

            // Convert amount
            const result = await convertCurrency(parseFloat(amount), fromCurrency, toCurrency);
            setConvertedAmount(result);
        } catch (err) {
            setError('Failed to convert currency. Please try again.');
            setConvertedAmount(null);
            setExchangeRate(null);
        } finally {
            setIsConverting(false);
        }
    };

    // Swap currencies
    const handleSwap = () => {
        const temp = fromCurrency;
        setFromCurrency(toCurrency);
        setToCurrency(temp);
    };

    // Handle amount input
    const handleAmountChange = (value: string) => {
        // Allow only numbers and decimal point
        const sanitized = value.replace(/[^0-9.]/g, '');
        // Ensure only one decimal point
        const parts = sanitized.split('.');
        if (parts.length > 2) {
            setAmount(parts[0] + '.' + parts.slice(1).join(''));
        } else {
            setAmount(sanitized);
        }
    };

    // Format number for display
    const formatCurrency = (value: number, decimals: number = 2): string => {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(value);
    };

    if (isLoading) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Currency Converter</CardTitle>
                    <CardDescription>Convert between available currencies</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <Skeleton className="h-20 w-full" />
                    <Skeleton className="h-20 w-full" />
                    <Skeleton className="h-16 w-full" />
                </CardContent>
            </Card>
        );
    }

    if (!currencies || currencies.length < 2) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Currency Converter</CardTitle>
                    <CardDescription>Convert between available currencies</CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert>
                        <AlertDescription>
                            At least 2 active currencies are required to use the converter.
                        </AlertDescription>
                    </Alert>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <span>Currency Converter</span>
                    <Button variant="ghost" size="sm" onClick={performConversion} disabled={isConverting}>
                        <RefreshCw className={`h-4 w-4 ${isConverting ? 'animate-spin' : ''}`} />
                    </Button>
                </CardTitle>
                <CardDescription>Convert between available currencies in real-time</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* From Currency */}
                <div className="space-y-2">
                    <Label htmlFor="from-currency">From</Label>
                    <div className="flex gap-2">
                        <Select value={fromCurrency} onValueChange={setFromCurrency}>
                            <SelectTrigger className="w-[140px]">
                                <SelectValue placeholder="Currency" />
                            </SelectTrigger>
                            <SelectContent>
                                {currencies.map((currency) => (
                                    <SelectItem key={currency.currency_id} value={currency.code}>
                                        {currency.code} - {currency.symbol}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Input
                            id="from-currency"
                            type="text"
                            value={amount}
                            onChange={(e) => handleAmountChange(e.target.value)}
                            placeholder="0.00"
                            className="flex-1 text-lg font-semibold"
                        />
                    </div>
                </div>

                {/* Swap Button */}
                <div className="flex justify-center">
                    <Button variant="outline" size="icon" onClick={handleSwap}>
                        <ArrowLeftRight className="h-4 w-4" />
                    </Button>
                </div>

                {/* To Currency */}
                <div className="space-y-2">
                    <Label htmlFor="to-currency">To</Label>
                    <div className="flex gap-2">
                        <Select value={toCurrency} onValueChange={setToCurrency}>
                            <SelectTrigger className="w-[140px]">
                                <SelectValue placeholder="Currency" />
                            </SelectTrigger>
                            <SelectContent>
                                {currencies.map((currency) => (
                                    <SelectItem key={currency.currency_id} value={currency.code}>
                                        {currency.code} - {currency.symbol}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <div className="flex-1 border rounded-md px-3 py-2 bg-muted/50">
                            <div className="text-lg font-semibold">
                                {isConverting ? (
                                    <span className="text-muted-foreground">Converting...</span>
                                ) : convertedAmount !== null ? (
                                    formatCurrency(convertedAmount)
                                ) : (
                                    <span className="text-muted-foreground">0.00</span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Exchange Rate Display */}
                {exchangeRate !== null && !error && (
                    <div className="border-t pt-4">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">Exchange Rate</span>
                            <div className="flex items-center gap-2">
                                {exchangeRate > 1 ? (
                                    <TrendingUp className="h-4 w-4 text-green-500" />
                                ) : exchangeRate < 1 ? (
                                    <TrendingDown className="h-4 w-4 text-blue-500" />
                                ) : null}
                                <span className="font-medium">
                                    1 {fromCurrency} = {formatCurrency(exchangeRate, 4)} {toCurrency}
                                </span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Conversion Summary */}
                {convertedAmount !== null && !error && parseFloat(amount) > 0 && (
                    <div className="bg-muted/30 rounded-lg p-4 space-y-2">
                        <div className="flex justify-between items-center">
                            <span className="text-sm text-muted-foreground">Original Amount</span>
                            <span className="font-medium">
                                {formatCurrency(parseFloat(amount))} {fromCurrency}
                            </span>
                        </div>
                        <div className="flex justify-between items-center text-lg">
                            <span className="text-sm text-muted-foreground">Converted Amount</span>
                            <span className="font-bold">
                                {formatCurrency(convertedAmount)} {toCurrency}
                            </span>
                        </div>
                    </div>
                )}

                {/* Error Message */}
                {error && (
                    <Alert variant="destructive">
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {/* Help Text */}
                <div className="text-xs text-muted-foreground text-center">
                    Exchange rates are updated regularly from external sources
                </div>
            </CardContent>
        </Card>
    );
}
