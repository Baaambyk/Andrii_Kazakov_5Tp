namespace Figury.Geometry;

/// <summary>Obliczenia geometryczne dla trójkąta.</summary>
public static class Triangle
{
    /// <summary>Pole trójkąta z podstawy i wysokości: P = (a · h) / 2.</summary>
    public static double AreaFromBaseAndHeight(double @base, double height)
    {
        if (@base <= 0 || height <= 0)
        {
            throw new ArgumentOutOfRangeException(nameof(@base), "Podstawa i wysokość muszą być dodatnie.");
        }

        return @base * height / 2.0;
    }

    /// <summary>Pole trójkąta z dwóch boków i kąta między nimi: P = (a · b · sin(γ)) / 2.</summary>
    public static double AreaFromSidesAndAngle(double a, double b, double angleDegrees)
    {
        if (a <= 0 || b <= 0)
        {
            throw new ArgumentOutOfRangeException(nameof(a), "Boki muszą być dodatnie.");
        }

        if (angleDegrees is <= 0 or >= 180)
        {
            throw new ArgumentOutOfRangeException(nameof(angleDegrees), "Kąt musi należeć do przedziału (0°, 180°).");
        }

        return a * b * Math.Sin(angleDegrees * Math.PI / 180.0) / 2.0;
    }

    /// <summary>
    /// Pole trójkąta z trzech boków (wzór Herona).
    /// Zwraca <c>false</c>, gdy z podanych boków nie można zbudować trójkąta.
    /// </summary>
    public static bool TryAreaFromSides(double a, double b, double c, out double area)
    {
        area = 0;

        if (a <= 0 || b <= 0 || c <= 0)
        {
            return false;
        }

        if (a + b <= c || a + c <= b || b + c <= a)
        {
            return false;
        }

        double p = (a + b + c) / 2.0;
        area = Math.Sqrt(p * (p - a) * (p - b) * (p - c));
        return true;
    }

    /// <summary>Obwód trójkąta: L = a + b + c.</summary>
    public static double Perimeter(double a, double b, double c) => a + b + c;
}
