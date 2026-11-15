namespace Figury.Geometry;

/// <summary>Obliczenia geometryczne dla rombu.</summary>
public static class Rhombus
{
    /// <summary>Pole rombu z przekątnych: P = (e · f) / 2.</summary>
    public static double AreaFromDiagonals(double e, double f)
    {
        if (e <= 0 || f <= 0)
        {
            throw new ArgumentOutOfRangeException(nameof(e), "Przekątne muszą być dodatnie.");
        }

        return e * f / 2.0;
    }

    /// <summary>Pole rombu z boku i wysokości: P = a · h.</summary>
    public static double AreaFromSideAndHeight(double a, double h)
    {
        if (a <= 0 || h <= 0)
        {
            throw new ArgumentOutOfRangeException(nameof(a), "Bok i wysokość muszą być dodatnie.");
        }

        return a * h;
    }

    /// <summary>Pole rombu z boku i kąta: P = a² · sin(α).</summary>
    public static double AreaFromSideAndAngle(double a, double angleDegrees)
    {
        if (a <= 0)
        {
            throw new ArgumentOutOfRangeException(nameof(a), "Bok musi być dodatni.");
        }

        if (angleDegrees is <= 0 or >= 180)
        {
            throw new ArgumentOutOfRangeException(nameof(angleDegrees), "Kąt musi należeć do przedziału (0°, 180°).");
        }

        return a * a * Math.Sin(ToRadians(angleDegrees));
    }

    /// <summary>Obwód rombu: L = 4 · a.</summary>
    public static double Perimeter(double a) => 4.0 * a;

    private static double ToRadians(double degrees) => degrees * Math.PI / 180.0;
}
